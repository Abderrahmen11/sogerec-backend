<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\Ticket::factory(20)->create()->each(function ($ticket) {
            \App\Models\Comment::factory(rand(0, 5))->create(['ticket_id' => $ticket->id]);
        });
    }
}
