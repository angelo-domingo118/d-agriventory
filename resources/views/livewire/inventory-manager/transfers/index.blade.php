<?php

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component
{
    public string $section = 'Transfers';
}

?>

@include('livewire.inventory-manager._layout', ['section' => $section])