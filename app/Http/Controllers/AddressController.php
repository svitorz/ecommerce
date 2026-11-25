<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAddressRequest;
use App\Models\Address;

class AddressController extends Controller
{
    public Address $address;

    public function store(StoreAddressRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $address = $user->address()->first() ?? new Address;
        $address->fill($validated);
        $user->address()->save($address);
    }
}
