<?php

use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Mary\Traits\Toast;

new class extends Component {
    use Toast;

    public string $search = '';

    public bool $drawer = false;
    public bool $addModal = false;
    public bool $editModal = false;

    public array $sortBy = ['column' => 'name', 'direction' => 'asc'];

    #[Validate('required|min:3')]
    public string $name = '';
    #[Validate('required|email')]
    public string $email = '';
    #[Validate('required')]
    public string $bod = '';
    #[Validate('required')]
    public string $userId = '';

    protected static ?string $password;

    // Clear filters
    public function clear(): void
    {
        $this->reset();
        $this->success('Filters cleared.', position: 'toast-bottom');
    }

    // Delete action
    public function delete(User $user): void
    {
        $user->delete();
        $this->warning("Will delete #$user->name", position: 'toast-bottom');
    }

    // Table headers
    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'name', 'label' => 'Name', 'class' => 'w-64'],
            ['key' => 'bod', 'label' => 'BOD', 'class' => 'w-32'],
            ['key' => 'birthday', 'label' => 'Birthday', 'class' => 'w-auto'],
            ['key' => 'age', 'label' => 'Age', 'class' => 'w-20'],
            ['key' => 'email', 'label' => 'E-mail', 'sortable' => false],
        ];
    }

    /**
     * For demo purpose, this is a static collection.
     *
     * On real projects you do it with Eloquent collections.
     * Please, refer to maryUI docs to see the eloquent examples.
     */
    public function users(): Collection
    {
        return User::all()
            ->sortBy([[...array_values($this->sortBy)]])
            ->when($this->search, function (Collection $collection) {
                return $collection->filter(fn(User $item) => str($item['name'])->contains($this->search, true));
            });
    }

    public function save()
    {
        $this->validate();

        User::create([
            'name'  => $this->name,
            'email' => $this->email,
            'bod'   => $this->bod,
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ]);

        $this->reset();
    }

    public function fillForm(User $user)
    {
        $this->id = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->bod = $user->bod;
        $this->userId = $user->id;
    }

    public function edit(User $user)
    {
        $this->fillForm($user);
        $this->editModal = true;
    }

    public function update()
    {
        $this->validate();

        $user = User::find($this->userId);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'bod' => $this->bod,
        ]);

        $this->reset();
    }

    public function with(): array
    {
        return [
            'users' => $this->users(),
            'headers' => $this->headers()
        ];
    }
}; ?>

<div>
    <!-- HEADER -->
    <x-header title="Users" separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="Filters" @click="$wire.drawer = true" responsive icon="o-funnel" />
            <x-button label="Add" @click="$wire.addModal = true" responsive icon="o-plus" />
        </x-slot:actions>
    </x-header>

    <!-- TABLE  -->
    <x-card>
        <x-table :headers="$headers" :rows="$users" :sort-by="$sortBy">
            {{-- @scope('cell_age', $user)
            {{ $user->age }}
            @endscope
            @scope('cell_birthday', $user)
            {{ $user->birthday }}
            @endscope --}}

            @scope('actions', $user)
            <div class="flex space-x-2">
                <x-button icon="o-pencil" wire:click="edit({{ $user['id'] }})" class="btn-ghost btn-sm text-gray-100" />
                <x-button icon="o-trash" wire:click="delete({{ $user['id'] }})" wire:confirm="Are you sure?" spinner
                    class="btn-ghost btn-sm text-red-500" />
            </div>
            @endscope
        </x-table>
    </x-card>

    <!-- ADD MODAL  -->
    <x-modal wire:model="addModal" title="User" subtitle="Add New User" separator>
        <x-form wire:submit="save">
            <x-input label="Name" wire:model="name" />
            <x-input label="Email" wire:model="email" />
            <x-datetime label="Birthday" wire:model="bod" icon="o-calendar" />

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.addModal = false" />
                <x-button label="Save" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
        Press `ESC`, click outside or click `CANCEL` button to close.
    </x-modal>

    <!-- EDIT MODAL  -->
    <x-modal wire:model="editModal" title="User" subtitle="Edit User" separator>
        <x-form wire:submit="update">
            <x-input wire:model="userId" hidden />
            <x-input label="Name" wire:model="name" />
            <x-input label="Email" wire:model="email" />
            <x-datetime label="Birthday" wire:model="bod" icon="o-calendar" />

            <x-slot:actions>
                <x-button label="Cancel" @click="$wire.editModal = false" />
                <x-button label="Update" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-form>
        Press `ESC`, click outside or click `CANCEL` button to close.
    </x-modal>

    <!-- FILTER DRAWER -->
    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-input placeholder="Search..." wire:model.live.debounce="search" icon="o-magnifying-glass"
            @keydown.enter="$wire.drawer = false" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clear" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>
</div>