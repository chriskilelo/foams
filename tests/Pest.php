<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Run the RegionScopeMiddleware for a given user so that any global scopes
 * it registers land on the model classes, exactly as during a real request.
 */
function applyRegionScopeMiddleware(\App\Models\User $user): void
{
    $request = \Illuminate\Http\Request::create('/dashboard', 'GET');
    $request->setUserResolver(fn () => $user);

    (new \App\Http\Middleware\RegionScopeMiddleware)->handle($request, fn () => new \Symfony\Component\HttpFoundation\Response);
}

/**
 * Remove RegionScope from the static global-scope registry so scopes
 * registered in one test cannot bleed into subsequent tests.
 */
function clearRegionScope(): void
{
    $ref = new ReflectionProperty(\Illuminate\Database\Eloquent\Model::class, 'globalScopes');
    $scopes = $ref->getValue(null);
    unset($scopes[\App\Models\Asset::class][\App\Models\Scopes\RegionScope::class]);
    unset($scopes[\App\Models\Issue::class][\App\Models\Scopes\RegionScope::class]);
    $ref->setValue(null, $scopes);
}
