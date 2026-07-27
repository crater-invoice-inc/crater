<?php

use Crater\Http\Controllers\V1\Admin\Expense\ExpensesController;
use Crater\Http\Requests\ExpenseRequest;
use Crater\Models\Expense;
use Crater\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('get expenses', function () {
    getJson('api/v1/expenses?page=1')->assertOk();
});

test('create expense', function () {
    $expense = Expense::factory()->raw([
        'amount' => 150,
        'exchange_rate' => 76.217498,
        'base_amount' => 11432.6247,
    ]);

    postJson('api/v1/expenses', $expense)->assertStatus(201);

    $this->assertDatabaseHas('expenses', [
        'notes' => $expense['notes'],
        'expense_category_id' => $expense['expense_category_id'],
        'amount' => $expense['amount'],
        'exchange_rate' => $expense['exchange_rate'],
        'base_amount' => $expense['base_amount'],
    ]);
});

test('store validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        ExpensesController::class,
        'store',
        ExpenseRequest::class
    );
});

test('get expense data', function () {
    $expense = Expense::factory()->create([
        'expense_date' => '2019-02-05',
    ]);

    getJson("api/v1/expenses/{$expense->id}")->assertOk();

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'notes' => $expense['notes'],
        'expense_category_id' => $expense['expense_category_id'],
        'amount' => $expense['amount'],
    ]);
});

test('update expense', function () {
    $expense = Expense::factory()->create([
        'expense_date' => '2019-02-05',
    ]);

    $expense2 = Expense::factory()->raw();

    putJson('api/v1/expenses/'.$expense->id, $expense2)->assertOk();

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'notes' => $expense2['notes'],
        'expense_category_id' => $expense2['expense_category_id'],
        'amount' => $expense2['amount'],
    ]);
});

test('update validates using a form request', function () {
    $this->assertActionUsesFormRequest(
        ExpensesController::class,
        'update',
        ExpenseRequest::class
    );
});

test('search expenses', function () {
    $filters = [
        'page' => 1,
        'limit' => 15,
        'expense_category_id' => 1,
        'search' => 'cate',
        'from_date' => '2020-07-18',
        'to_date' => '2020-07-20',
    ];

    $queryString = http_build_query($filters, '', '&');

    $response = getJson('api/v1/expenses?'.$queryString);

    $response->assertOk();
});

test('delete multiple expenses', function () {
    $expenses = Expense::factory()->count(3)->create([
        'expense_date' => '2019-02-05',
    ]);

    $data = [
        'ids' => $expenses->pluck('id'),
    ];

    $response = postJson('api/v1/expenses/delete', $data);

    $response
        ->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    foreach ($expenses as $expense) {
        $this->assertDeleted($expense);
    }
});

test('create expense with future due date is unpaid', function () {
    $expense = Expense::factory()->raw([
        'expense_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(15)->format('Y-m-d'),
    ]);
    unset($expense['paid_at']);

    postJson('api/v1/expenses', $expense)->assertStatus(201);

    $created = Expense::latest('id')->first();
    $this->assertNull($created->paid_at);
});

test('create expense without future due date defaults paid_at to expense date', function () {
    $expense = Expense::factory()->raw([
        'expense_date' => now()->format('Y-m-d'),
    ]);
    unset($expense['due_date'], $expense['paid_at']);

    postJson('api/v1/expenses', $expense)->assertStatus(201);

    $created = Expense::latest('id')->first();
    $this->assertNotNull($created->paid_at);
    $this->assertEquals($expense['expense_date'], $created->paid_at->format('Y-m-d'));
});

test('create expense with explicit paid_at keeps it', function () {
    $expense = Expense::factory()->raw([
        'expense_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(15)->format('Y-m-d'),
        'paid_at' => now()->addDays(3)->format('Y-m-d'),
    ]);

    postJson('api/v1/expenses', $expense)->assertStatus(201);

    $created = Expense::latest('id')->first();
    $this->assertNotNull($created->paid_at);
    $this->assertEquals($expense['paid_at'], $created->paid_at->format('Y-m-d'));
});

test('mark expense as paid sets paid_at to today', function () {
    $expense = Expense::factory()->create([
        'expense_date' => '2019-02-05',
        'paid_at' => null,
    ]);

    postJson("api/v1/expenses/{$expense->id}/mark-as-paid")->assertOk();

    $this->assertEquals(now()->toDateString(), $expense->fresh()->paid_at->toDateString());
});

test('filter expenses by paid status paid', function () {
    $paid = Expense::factory()->create([
        'expense_date' => '2019-02-05',
        'paid_at' => now(),
    ]);
    $unpaid = Expense::factory()->create([
        'expense_date' => '2019-02-05',
        'paid_at' => null,
    ]);

    $response = getJson('api/v1/expenses?paid_status=PAID&limit=all');
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    $this->assertContains($paid->id, $ids);
    $this->assertNotContains($unpaid->id, $ids);
});

test('filter expenses by paid status unpaid', function () {
    $paid = Expense::factory()->create([
        'expense_date' => '2019-02-05',
        'paid_at' => now(),
    ]);
    $unpaid = Expense::factory()->create([
        'expense_date' => '2019-02-05',
        'paid_at' => null,
    ]);

    $response = getJson('api/v1/expenses?paid_status=UNPAID&limit=all');
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    $this->assertContains($unpaid->id, $ids);
    $this->assertNotContains($paid->id, $ids);
});

test('dashboard upcoming expenses only lists unpaid within thirty days', function () {
    $upcoming = Expense::factory()->create([
        'due_date' => now()->addDays(10)->format('Y-m-d'),
        'paid_at' => null,
    ]);
    $paid = Expense::factory()->create([
        'due_date' => now()->addDays(10)->format('Y-m-d'),
        'paid_at' => now(),
    ]);
    $farFuture = Expense::factory()->create([
        'due_date' => now()->addDays(60)->format('Y-m-d'),
        'paid_at' => null,
    ]);

    $response = getJson('api/v1/dashboard');
    $response->assertOk();

    $ids = collect($response->json('upcoming_expenses'))->pluck('id')->all();
    $this->assertContains($upcoming->id, $ids);
    $this->assertNotContains($paid->id, $ids);
    $this->assertNotContains($farFuture->id, $ids);
});

test('update expense with EUR currency', function () {
    $expense = Expense::factory()->create([
        'expense_date' => '2019-02-05',
    ]);

    $expense2 = Expense::factory()->raw([
        'amount' => 150,
        'exchange_rate' => 76.217498,
        'base_amount' => 11432.6247,
    ]);

    putJson('api/v1/expenses/'.$expense->id, $expense2)->assertOk();

    $this->assertDatabaseHas('expenses', [
        'id' => $expense->id,
        'expense_category_id' => $expense2['expense_category_id'],
        'amount' => $expense2['amount'],
        'exchange_rate' => $expense2['exchange_rate'],
        'base_amount' => $expense2['base_amount'],
    ]);
});
