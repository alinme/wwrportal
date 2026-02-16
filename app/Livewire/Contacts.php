<?php

namespace App\Livewire;

use App\Models\Contact;
use App\Models\School;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['title' => 'Contacts'])]
class Contacts extends Component
{
    public string $search = '';

    public ?string $editingId = null;

    public string $edit_name = '';

    public string $edit_email = '';

    public string $edit_phone = '';

    public string $edit_organization = '';

    public string $edit_notes = '';

    public function openNew(): void
    {
        $this->editingId = null;
        $this->edit_name = '';
        $this->edit_email = '';
        $this->edit_phone = '';
        $this->edit_organization = '';
        $this->edit_notes = '';
        Flux::modal('contact-modal')->show();
    }

    public function edit(string $id): void
    {
        $contact = Contact::findOrFail($id);
        $this->editingId = $contact->id;
        $this->edit_name = $contact->name ?? '';
        $this->edit_email = $contact->email ?? '';
        $this->edit_phone = $contact->phone ?? '';
        $this->edit_organization = $contact->organization ?? '';
        $this->edit_notes = $contact->notes ?? '';
        Flux::modal('contact-modal')->show();
    }

    public function save(): void
    {
        $this->validate([
            'edit_name' => 'nullable|string|max:255',
            'edit_email' => 'nullable|email',
            'edit_phone' => 'nullable|string|max:255',
            'edit_organization' => 'nullable|string|max:255',
            'edit_notes' => 'nullable|string',
        ]);
        if (! $this->edit_name && ! $this->edit_email && ! $this->edit_phone) {
            Flux::toast(__('Provide at least name, email or phone.'), 'danger');

            return;
        }

        Contact::updateOrCreate(
            ['id' => $this->editingId],
            [
                'name' => $this->edit_name ?: null,
                'email' => $this->edit_email ?: null,
                'phone' => $this->edit_phone ?: null,
                'organization' => $this->edit_organization ?: null,
                'notes' => $this->edit_notes ?: null,
            ]
        );

        $this->reset(['editingId', 'edit_name', 'edit_email', 'edit_phone', 'edit_organization', 'edit_notes']);
        Flux::modal('contact-modal')->close();
        Flux::toast(__('Contact saved.'), 'success');
    }

    public function delete(string $id): void
    {
        Contact::findOrFail($id)->delete();
        Flux::toast(__('Contact deleted.'));
    }

    /**
     * Import contacts from school contact fields. Skips duplicates (same email, phone, or name+organization).
     */
    public function syncFromSchools(): void
    {
        $schools = School::query()
            ->where(function ($q) {
                $q->whereNotNull('contact_person')->where('contact_person', '!=', '')
                    ->orWhereNotNull('contact_phone')->where('contact_phone', '!=', '')
                    ->orWhereNotNull('contact_email')->where('contact_email', '!=', '');
            })
            ->get();

        $added = 0;
        foreach ($schools as $school) {
            $name = trim($school->contact_person ?? '') ?: null;
            $email = trim($school->contact_email ?? '') ?: null;
            $phone = trim($school->contact_phone ?? '') ?: null;
            $organization = trim($school->official_name ?? '') ?: null;

            if (! $name && ! $email && ! $phone) {
                continue;
            }

            $exists = Contact::query()
                ->where(function ($q) use ($email, $phone, $name, $organization) {
                    if ($email !== null && $email !== '') {
                        $q->orWhere('email', $email);
                    }
                    if ($phone !== null && $phone !== '') {
                        $q->orWhere('phone', $phone);
                    }
                    if ($name !== null && $organization !== null) {
                        $q->orWhere(fn ($q2) => $q2->where('name', $name)->where('organization', $organization));
                    }
                })
                ->exists();

            if (! $exists) {
                Contact::create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'organization' => $organization,
                    'notes' => null,
                ]);
                $added++;
            }
        }

        Flux::toast(__(':count new contact(s) synced from schools.', ['count' => $added]), 'success');
    }

    public function render()
    {
        $query = Contact::query()
            ->orderByRaw('name is null, name asc');

        if ($this->search !== '') {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term)
                    ->orWhere('phone', 'like', $term)
                    ->orWhere('organization', 'like', $term)
                    ->orWhere('notes', 'like', $term);
            });
        }

        $contacts = $query->get();

        return view('livewire.contacts', [
            'contacts' => $contacts,
        ]);
    }
}
