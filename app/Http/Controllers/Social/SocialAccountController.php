<?php

namespace App\Http\Controllers\Social;

use App\Http\Controllers\Controller;
use App\Http\Requests\Social\ConnectAccountRequest;
use App\Models\SocialAccount;
use App\Models\Team;
use App\Services\Social\Facebook\FacebookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
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
     * Test a raw access token without saving it.
     */
    public function testTokenRaw(Team $currentTeam): JsonResponse
    {
        Gate::authorize('create', [SocialAccount::class, $currentTeam]);

        $token = request()->input('access_token', '');
        $pageId = request()->input('page_id', '');

        if (! $token) {
            return response()->json(['valid' => false, 'message' => 'No token provided.'], 422);
        }

        $result = ['valid' => false, 'token_type' => 'unknown', 'message' => '', 'details' => []];

        try {
            $me = Http::get('https://graph.facebook.com/v21.0/me', [
                'access_token' => $token,
                'fields' => 'id,name,type',
            ]);

            if ($me->successful()) {
                $meData = $me->json();
                $result['user'] = $meData;
                $result['token_type'] = $meData['type'] ?? 'user';

                $pagesResp = Http::get('https://graph.facebook.com/v21.0/me/accounts', [
                    'access_token' => $token,
                ]);

                $pages = $pagesResp->json('data', []);
                $result['pages'] = $pages;
                $result['pages_count'] = count($pages);

                if (count($pages) > 0) {
                    $result['message'] = 'User token with '.count($pages).' page(s). When you save, it will auto-exchange for the matching page token.';
                } else {
                    $result['message'] = 'User token but no pages accessible. Add pages_show_list + pages_manage_posts permissions in Graph API Explorer.';
                }

                if ($pageId) {
                    $pageCheck = Http::get("https://graph.facebook.com/v21.0/{$pageId}", [
                        'access_token' => $token,
                        'fields' => 'id,name',
                    ]);

                    if ($pageCheck->successful()) {
                        $result['page_accessible'] = true;
                        $result['page_name'] = $pageCheck->json('name');
                        $result['message'] = 'Token works directly with page "'.$pageCheck->json('name').'"!';
                    } else {
                        $result['page_accessible'] = false;
                        $result['page_error'] = $pageCheck->json('error.message', 'Unknown');
                    }
                }

                $result['valid'] = true;
            } else {
                $result['message'] = 'Invalid token: '.$me->json('error.message', 'Unknown error');
            }
        } catch (\Exception $e) {
            $result['message'] = 'Error: '.$e->getMessage();
        }

        return response()->json($result);
    }

    /**
     * Store a manually connected social account.
     */
    public function store(ConnectAccountRequest $request, Team $currentTeam): RedirectResponse
    {
        Gate::authorize('create', [SocialAccount::class, $currentTeam]);

        $token = $request->access_token;
        $pageId = $request->page_id;

        if ($request->platform === 'facebook') {
            try {
                $token = $this->resolvePageAccessToken($token, $pageId);
            } catch (\Exception $e) {
                Inertia::flash('toast', ['type' => 'error', 'message' => __('Could not resolve page token: ').$e->getMessage()]);

                return to_route('social.accounts.index', ['current_team' => $currentTeam->slug]);
            }
        }

        SocialAccount::updateOrCreate(
            [
                'team_id' => $currentTeam->id,
                'platform_user_id' => $request->page_id,
                'platform' => $request->platform,
            ],
            [
                'name' => $request->name,
                'page_id' => $request->page_id,
                'instagram_account_id' => $request->instagram_account_id,
                'access_token' => $token,
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
        $scopes = 'pages_show_list,pages_manage_posts,pages_read_engagement,publish_video,instagram_basic,instagram_content_publish';

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
                $igAccount = $this->findInstagramAccount([$page]);

                SocialAccount::updateOrCreate(
                    [
                        'team_id' => $currentTeam->id,
                        'platform_user_id' => $userProfile['id'],
                        'platform' => 'facebook',
                    ],
                    [
                        'name' => $page['name'],
                        'page_id' => $page['id'],
                        'instagram_account_id' => $igAccount['instagram_account_id'] ?? null,
                        'access_token' => $page['access_token'],
                        'token_expires_at' => null,
                        'profile_picture_url' => $userProfile['picture']['data']['url'] ?? null,
                        'is_active' => true,
                    ],
                );
            }

            $igAccount = $this->findInstagramAccount($pages);

            if ($igAccount) {
                $igToken = $this->facebook->getPageAccessToken($tokenResponse['access_token'], $igAccount['page_id']);
                $igProfile = $this->fetchInstagramProfile($igToken, $igAccount['instagram_account_id']);

                SocialAccount::updateOrCreate(
                    [
                        'team_id' => $currentTeam->id,
                        'platform_user_id' => $userProfile['id'],
                        'platform' => 'instagram',
                    ],
                    [
                        'name' => $igProfile['name'] ?? 'Instagram Account',
                        'page_id' => $igAccount['instagram_account_id'],
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
     * Test a Facebook access token and return diagnostics.
     */
    public function testToken(Team $currentTeam, SocialAccount $account): JsonResponse
    {
        Gate::authorize('update', $account);

        try {
            $me = Http::get('https://graph.facebook.com/v21.0/me', [
                'access_token' => $account->access_token,
                'fields' => 'id,name',
            ]);

            $meData = $me->json();

            $pagesResponse = Http::get('https://graph.facebook.com/v21.0/me/accounts', [
                'access_token' => $account->access_token,
            ]);

            $pages = $pagesResponse->json('data', []);

            $isPageToken = count($pages) === 0;

            $tokenInfo = Http::get('https://graph.facebook.com/v21.0/me', [
                'access_token' => $account->access_token,
                'fields' => 'id,name',
                'appsecret_proof' => hash_hmac('sha256', $account->access_token, config('social.facebook.app_secret')),
            ]);

            return response()->json([
                'valid' => $me->successful(),
                'token_type' => $isPageToken ? 'page' : 'user',
                'user' => $meData,
                'pages' => $pages,
                'message' => $isPageToken
                    ? 'Token is a Page Access Token.'
                    : 'Token is a User Access Token. It will be auto-exchanged for a Page Token on save.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Token validation failed: '.$e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resolve a user access token to a page access token for the given page.
     */
    private function resolvePageAccessToken(string $token, string $pageId): string
    {
        $pagesResponse = Http::get('https://graph.facebook.com/v21.0/me/accounts', [
            'access_token' => $token,
        ]);

        if ($pagesResponse->successful()) {
            $pages = $pagesResponse->json('data', []);

            if (count($pages) > 0) {
                foreach ($pages as $page) {
                    if ($page['id'] === $pageId && isset($page['access_token'])) {
                        Log::info('Exchanged user token for page token', ['page_id' => $pageId, 'page_name' => $page['name']]);

                        return $page['access_token'];
                    }
                }

                throw new \RuntimeException("Page ID {$pageId} not found. Your accessible pages: ".implode(', ', array_map(fn ($p) => "{$p['name']} ({$p['id']})", $pages)));
            }
        }

        $pageCheck = Http::get("https://graph.facebook.com/v21.0/{$pageId}", [
            'access_token' => $token,
            'fields' => 'id,name',
        ]);

        if ($pageCheck->successful()) {
            Log::info('Token works directly as page token', ['page_id' => $pageId, 'page_name' => $pageCheck->json('name')]);

            return $token;
        }

        $errorMsg = $pagesResponse->json('error.message', '');

        if ($errorMsg !== '') {
            throw new \RuntimeException('Failed to list pages: '.$errorMsg.'. Ensure your token has pages_show_list + pages_manage_posts permissions.');
        }

        throw new \RuntimeException('Token is invalid or expired. Please generate a new token in Graph API Explorer with pages_show_list + pages_manage_posts + pages_read_engagement permissions.');
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
     *
     * @return array{page_id: string, instagram_account_id: string}|null
     */
    private function findInstagramAccount(array $pages): ?array
    {
        foreach ($pages as $page) {
            $igId = $this->facebook->getInstagramBusinessAccountId($page['access_token'], $page['id']);

            if ($igId) {
                return [
                    'page_id' => $page['id'],
                    'instagram_account_id' => $igId,
                ];
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
     * @return array{id: int, platform: string, name: string, pageId: string|null, instagramAccountId: string|null, profilePictureUrl: string|null, isActive: bool, createdAt: string}
     */
    private function formatAccount(SocialAccount $account): array
    {
        return [
            'id' => $account->id,
            'platform' => $account->platform,
            'name' => $account->name,
            'pageId' => $account->page_id,
            'instagramAccountId' => $account->instagram_account_id,
            'profilePictureUrl' => $account->profile_picture_url,
            'isActive' => $account->is_active,
            'createdAt' => $account->created_at->toIso8601String(),
        ];
    }
}
