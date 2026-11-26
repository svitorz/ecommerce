<?php

// O frontend chama a busca com a chave na rota e também na query string (ver SearchInput.vue).
// A validação lê a query string, então o teste envia as duas.
function searchRequest(string $key): array
{
    return ['url' => route('search', $key), 'parameters' => ['searchKey' => $key]];
}

it('permite cliente comum buscar produtos', function () {
    $req = searchRequest('camiseta');

    $this->actingAs(customer())
        ->call('GET', $req['url'], $req['parameters'])
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Products/IndexProducts'));
});

it('permite visitante buscar produtos sem login', function () {
    $req = searchRequest('camiseta');

    $this->call('GET', $req['url'], $req['parameters'])
        ->assertOk();
});

it('permite admin buscar produtos', function () {
    $req = searchRequest('camiseta');

    $this->actingAs(admin())
        ->call('GET', $req['url'], $req['parameters'])
        ->assertOk();
});
