<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

use function Pest\Laravel\mock;

use Spatie\LaravelData\Attributes\FromRouteParameter;

use Spatie\LaravelData\Data;

it('can fill data properties from a route model', function () {
    $dataClass = new class () extends Data {
        #[FromRouteParameter('something')]
        public int $id;
    };

    $somethingMock = new class () extends Model {
        protected $attributes = [
            'id' => 123,
        ];
    };

    $requestMock = mock(Request::class);
    $requestMock->expects('route')->with('something')->once()->andReturns($somethingMock);
    $requestMock->expects('toArray')->andReturns([]);

    $data = $dataClass::from($requestMock);

    expect($data->id)->toEqual(123);
});

it('can fill data properties from a route parameter that is an array', function () {
    $dataClass = new class () extends Data {
        #[FromRouteParameter('something')]
        public int $id;
    };

    $requestMock = mock(Request::class);
    $requestMock->expects('route')->with('something')->once()->andReturns(['id' => 123]);
    $requestMock->expects('toArray')->andReturns([]);

    $data = $dataClass::from($requestMock);

    expect($data->id)->toEqual(123);
});

it('can fill data properties from a route parameter using custom property mapping ', function () {
    $dataClass = new class () extends Data {
        #[FromRouteParameter('something', 'name')]
        public string $title;
        #[FromRouteParameter('something', 'nested.foo')]
        public string $foo;
    };

    $somethingMock = new class () extends Model {
        protected $attributes = [
            'name' => 'Something',
            'nested' => [
                'foo' => 'bar',
            ],
        ];
    };

    $requestMock = mock(Request::class);
    $requestMock->expects('route')->with('something')->twice()->andReturns($somethingMock);
    $requestMock->expects('toArray')->andReturns([]);

    $data = $dataClass::from($requestMock);

    expect($data->title)->toEqual('Something');
    expect($data->foo)->toEqual('bar');
});

it('replaces properties when route parameter properties exist', function () {
    $dataClass = new class () extends Data {
        #[FromRouteParameter('something')]
        public string $name;
    };

    $somethingMock = new class () extends Model {
        protected $attributes = [
            'name' => 'Best',
        ];
    };

    $requestMock = mock(Request::class);
    $requestMock->expects('route')->with('something')->once()->andReturns($somethingMock);
    $requestMock->expects('toArray')->andReturns(['title' => 'Better']);

    $data = $dataClass::from($requestMock);

    expect($data->name)->toEqual('Best');
});

it('skips replacing properties when route parameter properties exist and replacing is disabled', function () {
    $dataClass = new class () extends Data {
        #[FromRouteParameter('something', replaceWhenPresentInBody: false)]
        public string $name;
        #[FromRouteParameter('something', 'long', false)]
        public string $description;
    };

    $requestMock = mock(Request::class);
    $requestMock->expects('route')->with('something')->never();
    $requestMock->expects('toArray')->andReturns(['name' => 'Better', 'description' => 'Description']);

    $data = $dataClass::from($requestMock);

    expect($data->name)->toEqual('Better');
    expect($data->description)->toEqual('Description');
});

it('skips properties it cannot find a route parameter for', function () {
    $dataClass = new class () extends Data {
        #[FromRouteParameter('something')]
        public string $name;
    };

    $requestMock = mock(Request::class);
    $requestMock->expects('route')->with('something')->once()->andReturnNull();
    $requestMock->expects('toArray')->andReturns(['name' => 'Better']);

    $data = $dataClass::from($requestMock);

    expect($data->name)->toEqual('Better');
});