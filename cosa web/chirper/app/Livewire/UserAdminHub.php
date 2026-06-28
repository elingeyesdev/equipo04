<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Validation\Rule;

class UserAdminHub extends Component
{
    use WithPagination;

    public string $search = '';
    public string $roleFilter = '';
    public bool $showCreateModal = false;

    // Campos para crear nueva autoridad
    public string $newName = '';
    public string $newCarnet = '';
    public string $newPhone = '';
    public string $newPassword = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function promoteToAuthority(string $carnet): void
    {
        $user = User::where('carnet', $carnet)->first();
        if ($user && $user->role === User::ROLE_CITIZEN) {
            $user->update(['role' => User::ROLE_AUTHORITY]);
            session()->flash('message', 'Usuario promovido a Autoridad con éxito.');
        }
    }

    public function promoteToSuperAdmin(string $carnet): void
    {
        $user = User::where('carnet', $carnet)->first();
        if ($user && in_array($user->role, [User::ROLE_CITIZEN, User::ROLE_AUTHORITY])) {
            $user->update(['role' => User::ROLE_SUPER_ADMIN]);
            session()->flash('message', 'Usuario promovido a Super Admin con éxito.');
        }
    }

    public function demoteUser(string $carnet): void
    {
        $user = User::where('carnet', $carnet)->first();
        if ($user && in_array($user->role, [User::ROLE_AUTHORITY, User::ROLE_SUPER_ADMIN])) {
            if ($user->role === User::ROLE_SUPER_ADMIN) {
                $superAdminCount = User::where('role', User::ROLE_SUPER_ADMIN)->count();
                if ($superAdminCount <= 3) {
                    $this->dispatch('swal', [
                        'title' => 'Acción Denegada',
                        'text' => 'No puedes quitar el rol de Super Admin. El sistema requiere al menos 3 superadmins activos en todo momento.',
                        'icon' => 'error'
                    ]);
                    return;
                }
            }
            
            $user->update(['role' => User::ROLE_CITIZEN]);
            session()->flash('message', 'Usuario removido de sus cargos administrativos con éxito.');
        }
    }

    public function openCreateModal(): void
    {
        $this->reset(['newName', 'newCarnet', 'newPhone', 'newPassword']);
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
    }

    public function createAuthority(): void
    {
        $this->validate([
            'newCarnet' => ['required', 'string', 'max:20', Rule::unique('users', 'carnet')],
            'newName' => ['required', 'string', 'max:255'],
            'newPhone' => ['required', 'string', 'max:20'],
            'newPassword' => ['required', 'string', 'min:8'],
        ], [
            'newCarnet.unique' => 'Ya existe un usuario con este carnet.',
        ]);

        User::create([
            'carnet' => $this->newCarnet,
            'name' => $this->newName,
            'phone' => $this->newPhone,
            'password' => Hash::make($this->newPassword),
            'role' => User::ROLE_AUTHORITY,
            'is_banned' => false,
            // Valores requeridos por default
            'email' => strtolower(str_replace(' ', '', $this->newCarnet)) . '@autoridad.local',
            'address' => 'N/A'
        ]);

        $this->closeCreateModal();
        session()->flash('message', 'Cuenta de autoridad creada exitosamente.');
    }

    public function render()
    {
        $stats = [
            'total' => User::count(),
            'citizens' => User::where('role', User::ROLE_CITIZEN)->count(),
            'authorities' => User::where('role', User::ROLE_AUTHORITY)->count(),
            'super_admins' => User::where('role', User::ROLE_SUPER_ADMIN)->count(),
        ];

        $users = User::query()
            ->when($this->search !== '', function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'ilike', '%' . $this->search . '%')
                      ->orWhere('carnet', 'ilike', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter !== '', function ($query) {
                $query->where('role', $this->roleFilter);
            })
            ->orderBy('name')
            ->paginate(10);

        return view('livewire.user-admin-hub', [
            'users' => $users,
            'stats' => $stats,
        ])->layout('layouts.app');
    }
}
