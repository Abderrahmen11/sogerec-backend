<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Comment;
use App\Models\Ticket;
use App\Services\TicketService;
use Illuminate\Support\Facades\Auth;

class TicketCommentController extends Controller
{
    //
    protected TicketService $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    public function index($ticketId, Request $request)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $comments = Comment::with('user')->where('ticket_id', $ticket->id)->orderBy('created_at', 'asc')->get();
        return response()->json($comments);
    }

    public function store($ticketId, Request $request)
    {
        $request->validate(['content' => 'required|string']);
        $ticket = Ticket::findOrFail($ticketId);
        $this->ticketService->addComment($ticket, $request->content);
        return response()->json(['message' => 'Comment added'], 201);
    }

    public function destroy($id, Request $request)
    {
        $comment = Comment::findOrFail($id);
        $user = $request->user();
        if ($user->role !== 'admin' && $comment->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $comment->delete();
        return response()->json(['message' => 'Comment deleted']);
    }
}
