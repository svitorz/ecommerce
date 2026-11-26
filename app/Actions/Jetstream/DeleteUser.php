<?php

namespace App\Actions\Jetstream;

use App\Models\User;
use Illuminate\Validation\ValidationException;
use Laravel\Jetstream\Contracts\DeletesUsers;

class DeleteUser implements DeletesUsers
{
    /**
     * Delete the given user.
     */
    public function delete(User $user): void
    {
        if ($user->orders()->exists()) {
            throw ValidationException::withMessages([
                'password' => 'Não é possível excluir a conta com pedidos registrados.',
            ]);
        }

        $user->deleteProfilePhoto();
        $user->tokens->each->delete();
        $user->delete();
    }
}
