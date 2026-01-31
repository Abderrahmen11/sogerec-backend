<?php

/**
 * System Verification Tests
 * 
 * This document outlines all tests that MUST pass to verify the system fixes
 * Run these tests manually via API client (Postman, Insomnia, etc.) or Laravel tests
 */

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Intervention;
use App\Models\Planning;
use Illuminate\Database\Eloquent\Factories\Sequence;

class SystemVerificationTest extends TestCase
{
    /**
     * TEST 1: Client cannot access /interventions API endpoint
     * Expected: 403 Forbidden or redirect
     */
    public function test_client_cannot_access_interventions_api()
    {
        $client = User::factory()->create(['role' => 'client']);
        $token = $client->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/interventions');

        // Should be forbidden or unauthorized
        $this->assertTrue(in_array($response->status(), [403, 401]));
    }

    /**
     * TEST 2: Client cannot access /planning API endpoint
     * Expected: 403 Forbidden or redirect
     */
    public function test_client_cannot_access_planning_api()
    {
        $client = User::factory()->create(['role' => 'client']);
        $token = $client->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/planning');

        // Should be forbidden or unauthorized
        $this->assertTrue(in_array($response->status(), [403, 401]));
    }

    /**
     * TEST 3: Technician can access /interventions API endpoint
     * Expected: 200 OK
     */
    public function test_technician_can_access_interventions_api()
    {
        $technician = User::factory()->create(['role' => 'technician']);
        $token = $technician->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/interventions');

        $this->assertEquals(200, $response->status());
    }

    /**
     * TEST 4: Admin can access /interventions API endpoint
     * Expected: 200 OK
     */
    public function test_admin_can_access_interventions_api()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/interventions');

        $this->assertEquals(200, $response->status());
    }

    /**
     * TEST 5: Admin can create intervention (assign technician) and it persists in DB
     * Expected: 201 Created, intervention in database with technician_id set
     */
    public function test_admin_can_create_intervention_and_assign_technician()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $client = User::factory()->create(['role' => 'client']);
        $ticket = Ticket::factory()->create(['user_id' => $client->id]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/interventions', [
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'scheduled_at' => now()->addDay(),
                'title' => 'Test Intervention',
                'description' => 'Testing assignment'
            ]);

        $this->assertEquals(201, $response->status());

        // Verify intervention is in database
        $this->assertDatabaseHas('interventions', [
            'ticket_id' => $ticket->id,
            'user_id' => $technician->id,
        ]);

        // Verify ticket assignment is updated
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $technician->id,
        ]);
    }

    /**
     * TEST 6: Planning record is created when technician is assigned with scheduled_at
     * Expected: Planning entry exists in database
     */
    public function test_planning_created_when_intervention_assigned()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $client = User::factory()->create(['role' => 'client']);
        $ticket = Ticket::factory()->create(['user_id' => $client->id]);

        $token = $admin->createToken('test')->plainTextToken;
        $scheduledDate = now()->addDay();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/interventions', [
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'scheduled_at' => $scheduledDate,
            ]);

        $this->assertEquals(201, $response->status());

        $intervention = Intervention::where('ticket_id', $ticket->id)->first();

        // Verify planning entry exists
        $this->assertDatabaseHas('plannings', [
            'intervention_id' => $intervention->id,
            'technician_id' => $technician->id,
        ]);
    }

    /**
     * TEST 7: Only ONE notification is sent when technician is assigned (no duplicates)
     * Expected: Exactly 1 notification to technician, 1 to client
     */
    public function test_no_duplicate_notifications_on_assignment()
    {
        // This test requires checking the notifications table
        // After assignment, count notifications and verify exactly 2 exist (1 tech, 1 client)

        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $client = User::factory()->create(['role' => 'client']);
        $ticket = Ticket::factory()->create(['user_id' => $client->id]);

        $token = $admin->createToken('test')->plainTextToken;

        // Clear any existing notifications
        $technicianNotificationsBefore = $technician->notifications()->count();
        $clientNotificationsBefore = $client->notifications()->count();

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/interventions', [
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'scheduled_at' => now()->addDay(),
            ]);

        $this->assertEquals(201, $response->status());

        // Count notifications after
        $technicianNotificationsAfter = $technician->notifications()->count();
        $clientNotificationsAfter = $client->notifications()->count();

        // Exactly 1 new notification to each
        $this->assertEquals(1, $technicianNotificationsAfter - $technicianNotificationsBefore);
        $this->assertEquals(1, $clientNotificationsAfter - $clientNotificationsBefore);
    }

    /**
     * TEST 8: Admin can generate report for completed intervention
     * Expected: 201 Created, report in database linked to intervention
     */
    public function test_admin_can_generate_report_for_completed_intervention()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $intervention = Intervention::factory()->create([
            'user_id' => $technician->id,
            'status' => 'completed'
        ]);

        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/reports', [
                'intervention_id' => $intervention->id,
                'title' => 'Test Report',
                'summary' => 'Work completed successfully',
                'findings' => 'No issues found',
                'recommendations' => 'Regular maintenance recommended'
            ]);

        $this->assertEquals(201, $response->status());

        // Verify report is in database
        $this->assertDatabaseHas('intervention_reports', [
            'intervention_id' => $intervention->id,
            'technician_id' => $technician->id,
        ]);
    }

    /**
     * TEST 9: Admin can fetch all reports via /api/reports
     * Expected: 200 OK, returns array of reports
     */
    public function test_admin_can_fetch_reports()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/reports');

        $this->assertEquals(200, $response->status());
        $this->assertIsArray($response->json()['data'] ?? $response->json());
    }

    /**
     * TEST 10: Interventions list updates after assignment
     * Expected: GET /api/interventions returns newly created intervention
     */
    public function test_interventions_list_includes_newly_created_intervention()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $client = User::factory()->create(['role' => 'client']);
        $ticket = Ticket::factory()->create(['user_id' => $client->id]);

        $token = $admin->createToken('test')->plainTextToken;

        // Create intervention
        $createResponse = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/interventions', [
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'scheduled_at' => now()->addDay(),
            ]);

        $this->assertEquals(201, $createResponse->status());

        // Fetch interventions list
        $listResponse = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/interventions');

        $this->assertEquals(200, $listResponse->status());

        // Verify intervention is in the list
        $interventions = $listResponse->json();
        $found = collect($interventions)->contains(function ($item) use ($ticket) {
            return $item['ticket_id'] === $ticket->id;
        });

        $this->assertTrue($found, 'Newly created intervention not found in list');
    }

    /**
     * TEST 11: Planning list shows scheduled interventions
     * Expected: GET /api/planning returns planning entries with intervention data
     */
    public function test_planning_list_shows_scheduled_interventions()
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $technician = User::factory()->create(['role' => 'technician']);
        $client = User::factory()->create(['role' => 'client']);
        $ticket = Ticket::factory()->create(['user_id' => $client->id]);

        $token = $admin->createToken('test')->plainTextToken;

        // Create scheduled intervention
        $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/interventions', [
                'ticket_id' => $ticket->id,
                'user_id' => $technician->id,
                'scheduled_at' => now()->addDay(),
            ]);

        // Fetch planning
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/planning');

        $this->assertEquals(200, $response->status());

        $planning = $response->json();
        $this->assertNotEmpty($planning, 'Planning list should not be empty');
    }

    /**
     * TEST 12: Technician sees only their assigned interventions
     * Expected: GET /api/interventions returns only interventions assigned to technician
     */
    public function test_technician_sees_only_their_interventions()
    {
        $tech1 = User::factory()->create(['role' => 'technician']);
        $tech2 = User::factory()->create(['role' => 'technician']);
        $client = User::factory()->create(['role' => 'client']);

        $intervention1 = Intervention::factory()->create(['user_id' => $tech1->id]);
        $intervention2 = Intervention::factory()->create(['user_id' => $tech2->id]);

        $token = $tech1->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/interventions');

        $interventions = $response->json();

        // Should only have tech1's intervention
        $this->assertTrue(
            collect($interventions)->contains('id', $intervention1->id),
            'Should contain tech1 intervention'
        );

        $this->assertFalse(
            collect($interventions)->contains('id', $intervention2->id),
            'Should NOT contain tech2 intervention'
        );
    }
}
