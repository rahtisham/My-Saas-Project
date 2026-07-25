<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\Social\ConnectAccountRequest;
use App\Models\SocialAccount;
use App\Models\Team;
use App\Services\Social\Facebook\FacebookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Inertia\Response;

class SocialAccountController extends Controller
{
    public function __construct(
        private FacebookService $facebook,
    ) {}

    /**
     * Display a listing of the team's social accounts.
     */
    public function index(Team $currentTeam): Response
    {
        Gate::authorize('viewAny', [SocialAccount::class, $currentTeam]);

        $accounts = $currentTeam->socialAccounts()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SocialAccount $account) => $this->formatAccount($account));

        return Inertia::render('social/accounts/Index', [
            'accounts' => $accounts,
        ]);
    }

    /**
     * Store a manually connected social account.
     */
    public function store(ConnectAccountRequest $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [SocialAccount::class, $currentTeam]);

        SocialAccount::updateOrCreate(
            [
                'team_id' => $currentTeam->id,
                'platform_user_id' => $request->page_id,
                'platform' => $request->platform,
            ],
            [
                'name' => $request->name,
                'page_id' => $request->page_id,
                'access_token' => $request->access_token,
                'is_active' => true,
            ],
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Social account connected.')]);

        return to_route('social.accounts.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Redirect the user to the Facebook OAuth authorization page.
     */
    public function redirect(Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [SocialAccount::class, $currentTeam]);

        $appId = config('social.facebook.app_id');
        $redirectUri = route('social.accounts.callback', ['current_team' => $currentTeam->slug]);
        $scopes = 'pages_show_list,pages_manage_posts,instagram_basic,instagram_content_publish';

        $url = 'https://www.facebook.com/v21.0/dialog/oauth?'.http_build_query([
            'client_id' => $appId,
            'redirect_uri' => $redirectUri,
            'scope' => $scopes,
            'state' => encrypt(['team_id' => $currentTeam->id]),
        ]);

        return redirect($url);
    }

    /**
     * Handle the Facebook OAuth callback.
     */
    public function callback(Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [SocialAccount::class, $currentTeam]);

        $code = request()->query('code');

        if (! $code) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Authorization was cancelled or failed.')]);

            return to_route('social.accounts.index', ['current_team' => $currentTeam->slug]);
        }

        try {
            $tokenResponse = $this->exchangeCodeForToken($code, $currentTeam);
            $userProfile = $this->fetchUserProfile($tokenResponse['access_token']);
            $pages = $this->fetchUserPages($tokenResponse['access_token']);

            foreach ($pages as $page) {
                SocialAccount::updateOrCreate(
                    [
                        'team_id' => $currentTeam->id,
                        'platform_user_id' => $userProfile['id'],
                        'platform' => 'facebook',
                    ],
                    [
                        'name' => $page['name'],
                        'page_id' => $page['id'],
                        'access_token' => $page['access_token'],
                        'token_expires_at' => null,
                        'profile_picture_url' => $userProfile['picture']['data']['url'] ?? null,
                        'is_active' => true,
                    ],
                );
            }

            $instagramAccountId = $this->findInstagramAccount($pages);

            if ($instagramAccountId) {
                $igToken = $this->facebook->getPageAccessToken($tokenResponse['access_token'], $instagramAccountId);
                $igProfile = $this->fetchInstagramProfile($igToken, $instagramAccountId);

                SocialAccount::updateOrCreate(
                    [
                        'team_id' => $currentTeam->id,
                        'platform_user_id' => $userProfile['id'],
                        'platform' => 'instagram',
                    ],
                    [
                        'name' => $igProfile['name'] ?? 'Instagram Account',
                        'page_id' => $instagramAccountId,
                        'access_token' => $igToken,
                        'token_expires_at' => null,
                        'profile_picture_url' => $igProfile['profile_picture_url'] ?? null,
                        'is_active' => true,
                    ],
                );
            }

            Inertia::flash('toast', ['type' => 'success', 'message' => __('Social accounts connected successfully.')]);
        } catch (\Exception $e) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('Failed to connect account: ').$e->getMessage()]);
        }

        return to_route('social.accounts.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Remove the specified social account.
     */
    public function destroy(Team $currentTeam, SocialAccount $account): RedirectResponse
    {
        Gate::authorize('delete', $account);

        $account->update(['is_active' => false]);
        $account->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Social account disconnected.')]);

        return to_route('social.accounts.index', ['current_team' => $currentTeam->slug]);
    }

    /**
     * Exchange the authorization code for a user access token.
     *
     * @return array{access_token: string}
     */
    private function exchangeCodeForToken(string $code, Team $team): array
    {
        $response = Http::get('https://graph.facebook.com/v21.0/oauth/access_token', [
            'client_id' => config('social.facebook.app_id'),
            'client_secret' => config('social.facebook.app_secret'),
            'redirect_uri' => route('social.accounts.callback', ['current_team' => $team->slug]),
            'code' => $code,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to exchange authorization code for token.');
        }

        return $response->json();
    }

    /**
     * Fetch the user's Facebook profile.
     *
     * @return array<string, mixed>
     */
    private function fetchUserProfile(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/v21.0/me', [
            'access_token' => $accessToken,
            'fields' => 'id,name,picture',
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch user profile.');
        }

        return $response->json();
    }

    /**
     * Fetch the user's managed Facebook pages.
     *
     * @return list<array{id: string, name: string, access_token: string}>
     */
    private function fetchUserPages(string $accessToken): array
    {
        $response = Http::get('https://graph.facebook.com/v21.0/me/accounts', [
            'access_token' => $accessToken,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Failed to fetch managed pages.');
        }

        return $response->json('data', []);
    }

    /**
     * Find an Instagram business account linked to one of the pages.
     */
    private function findInstagramAccount(array $pages): ?string
    {
        foreach ($pages as $page) {
            $igId = $this->facebook->getInstagramBusinessAccountId($page['access_token'], $page['id']);

            if ($igId) {
                return $page['id'];
            }
        }

        return null;
    }

    /**
     * Fetch Instagram profile details.
     *
     * @return array<string, mixed>
     */
    private function fetchInstagramProfile(string $accessToken, string $pageId): array
    {
        $response = Http::get("https://graph.facebook.com/v21.0/{$pageId}", [
            'access_token' => $accessToken,
            'fields' => 'name,profile_picture_url',
        ]);

        if ($response->failed()) {
            return ['name' => 'Instagram Account'];
        }

        return $response->json();
    }

    /**
     * Format a social account for the frontend.
     *
     * @return array{id: int, platform: string, name: string, pageId: string|null, profilePictureUrl: string|null, isActive: bool, createdAt: string}
     */
    private function formatAccount(SocialAccount $account): array
    {
        return [
            'id' => $account->id,
            'platform' => $account->platform,
            'name' => $account->name,
            'pageId' => $account->page_id,
            'profilePictureUrl' => $account->profile_picture_url,
            'isActive' => $account->is_active,
            'createdAt' => $account->created_at->toIso8601String(),
        ];
    }
}
