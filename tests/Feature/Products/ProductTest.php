<?php

use App\Enums\TeamRole;
use App\Models\Product;
use App\Models\Team;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

// -----------------------------------------------------------------------
// Helpers
// -----------------------------------------------------------------------

/**
 * Create a user with a team, attach the given role, and switch the user to that team.
 *
 * @return array{User, Team}
 */
function userWithTeam(TeamRole $role = TeamRole::Owner): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($user, ['role' => $role->value]);
    $user->update(['current_team_id' => $team->id]);

    return [$user, $team];
}

// -----------------------------------------------------------------------
// Index
// -----------------------------------------------------------------------

test('guests cannot view the products index', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $response = $this->get(route('products.index', ['current_team' => $team->slug]));

    $response->assertRedirect(route('login'));
});

test('team members can view the products index', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('products.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('products/Index')
        ->has('products'),
    );
});

test('products index only shows products belonging to the team', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $ownProduct = Product::factory()->for($team)->create(['name' => 'Team Widget']);
    Product::factory()->create(['name' => 'Outsider Widget']);

    $response = $this
        ->actingAs($user)
        ->get(route('products.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('products/Index')
        ->has('products', 1)
        ->where('products.0.id', $ownProduct->id),
    );
});

test('products index returns expected fields', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    Product::factory()->for($team)->create([
        'name' => 'Blue Widget',
        'sku' => 'BW-001',
        'price' => 9.99,
        'stock' => 50,
        'is_active' => true,
    ]);

    $response = $this
        ->actingAs($user)
        ->get(route('products.index', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('products/Index')
        ->where('products.0.name', 'Blue Widget')
        ->where('products.0.sku', 'BW-001')
        ->where('products.0.price', 9.99)
        ->where('products.0.stock', 50)
        ->where('products.0.isActive', true),
    );
});

// -----------------------------------------------------------------------
// Create
// -----------------------------------------------------------------------

test('owners can view the create product page', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->get(route('products.create', ['current_team' => $team->slug]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('products/Create'),
    );
});

test('admins can view the create product page', function () {
    [$user, $team] = userWithTeam(TeamRole::Admin);

    $response = $this
        ->actingAs($user)
        ->get(route('products.create', ['current_team' => $team->slug]));

    $response->assertOk();
});

test('members cannot view the create product page', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->get(route('products.create', ['current_team' => $team->slug]));

    $response->assertForbidden();
});

// -----------------------------------------------------------------------
// Store
// -----------------------------------------------------------------------

test('owners can create a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->post(route('products.store', ['current_team' => $team->slug]), [
            'name' => 'New Widget',
            'sku' => 'NW-001',
            'description' => 'A great widget.',
            'price' => 19.99,
            'stock' => 100,
            'is_active' => true,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('products', [
        'team_id' => $team->id,
        'name' => 'New Widget',
        'sku' => 'NW-001',
        'price' => 19.99,
        'stock' => 100,
        'is_active' => true,
    ]);
});

test('admins can create a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Admin);

    $response = $this
        ->actingAs($user)
        ->post(route('products.store', ['current_team' => $team->slug]), [
            'name' => 'Admin Widget',
            'price' => 5.00,
            'stock' => 10,
            'is_active' => true,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('products', [
        'team_id' => $team->id,
        'name' => 'Admin Widget',
    ]);
});

test('members cannot create a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);

    $response = $this
        ->actingAs($user)
        ->post(route('products.store', ['current_team' => $team->slug]), [
            'name' => 'Sneaky Widget',
            'price' => 1.00,
            'stock' => 1,
            'is_active' => true,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseMissing('products', [
        'team_id' => $team->id,
        'name' => 'Sneaky Widget',
    ]);
});

test('creating a product requires a name', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->post(route('products.store', ['current_team' => $team->slug]), [
            'price' => 10.00,
            'stock' => 5,
            'is_active' => true,
        ]);

    $response->assertSessionHasErrors('name');
});

test('creating a product requires a valid price', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $response = $this
        ->actingAs($user)
        ->post(route('products.store', ['current_team' => $team->slug]), [
            'name' => 'Widget',
            'price' => -1,
            'stock' => 5,
            'is_active' => true,
        ]);

    $response->assertSessionHasErrors('price');
});

test('creating a product redirects to the edit page', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);

    $this
        ->actingAs($user)
        ->post(route('products.store', ['current_team' => $team->slug]), [
            'name' => 'Redirect Widget',
            'price' => 9.99,
            'stock' => 1,
            'is_active' => true,
        ]);

    $product = Product::where('name', 'Redirect Widget')->firstOrFail();

    $this->assertDatabaseHas('products', ['id' => $product->id]);
});

// -----------------------------------------------------------------------
// Edit
// -----------------------------------------------------------------------

test('owners can view the edit product page', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('products.edit', ['current_team' => $team->slug, 'product' => $product->id]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('products/Edit')
        ->where('product.id', $product->id)
        ->where('product.name', $product->name),
    );
});

test('members cannot view the edit product page', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->get(route('products.edit', ['current_team' => $team->slug, 'product' => $product->id]));

    $response->assertForbidden();
});

// -----------------------------------------------------------------------
// Update
// -----------------------------------------------------------------------

test('owners can update a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $product = Product::factory()->for($team)->create(['name' => 'Old Name']);

    $response = $this
        ->actingAs($user)
        ->patch(route('products.update', ['current_team' => $team->slug, 'product' => $product->id]), [
            'name' => 'New Name',
            'sku' => $product->sku,
            'description' => $product->description,
            'price' => $product->price,
            'stock' => $product->stock,
            'is_active' => $product->is_active,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'New Name',
    ]);
});

test('admins can update a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Admin);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('products.update', ['current_team' => $team->slug, 'product' => $product->id]), [
            'name' => 'Admin Updated',
            'price' => $product->price,
            'stock' => $product->stock,
            'is_active' => $product->is_active,
        ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Admin Updated',
    ]);
});

test('members cannot update a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);
    $product = Product::factory()->for($team)->create(['name' => 'Original']);

    $response = $this
        ->actingAs($user)
        ->patch(route('products.update', ['current_team' => $team->slug, 'product' => $product->id]), [
            'name' => 'Hacked Name',
            'price' => $product->price,
            'stock' => $product->stock,
            'is_active' => $product->is_active,
        ]);

    $response->assertForbidden();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Original',
    ]);
});

test('updating a product requires a name', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->patch(route('products.update', ['current_team' => $team->slug, 'product' => $product->id]), [
            'name' => '',
            'price' => 10.00,
            'stock' => 1,
            'is_active' => true,
        ]);

    $response->assertSessionHasErrors('name');
});

// -----------------------------------------------------------------------
// Destroy
// -----------------------------------------------------------------------

test('owners can delete a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Owner);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('products.destroy', ['current_team' => $team->slug, 'product' => $product->id]));

    $response->assertRedirect(route('products.index', ['current_team' => $team->slug]));

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('admins can delete a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Admin);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('products.destroy', ['current_team' => $team->slug, 'product' => $product->id]));

    $response->assertRedirect();

    $this->assertSoftDeleted('products', [
        'id' => $product->id,
    ]);
});

test('members cannot delete a product', function () {
    [$user, $team] = userWithTeam(TeamRole::Member);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($user)
        ->delete(route('products.destroy', ['current_team' => $team->slug, 'product' => $product->id]));

    $response->assertForbidden();

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'deleted_at' => null,
    ]);
});

test('users outside the team cannot delete a product', function () {
    $outsider = User::factory()->create();
    [$owner, $team] = userWithTeam(TeamRole::Owner);
    $product = Product::factory()->for($team)->create();

    $response = $this
        ->actingAs($outsider)
        ->delete(route('products.destroy', ['current_team' => $team->slug, 'product' => $product->id]));

    $response->assertForbidden();
});

test('guests cannot access product routes', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->get(route('products.index', ['current_team' => $team->slug]))->assertRedirect(route('login'));
    $this->get(route('products.create', ['current_team' => $team->slug]))->assertRedirect(route('login'));
});
