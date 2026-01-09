<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bill;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class BillController extends Controller
{

    private function createNotification(string $message)
{
    Notification::create([
        'user_id' => Auth::id(),
        'message' => $message,
    ]);
}
    public function index()
    {
        return response()->json(Bill::where('user_id', Auth::id())->get());
    }

 public function show($id)
{
    $bill = Bill::find($id);
    if (!$bill) {
        return response()->json(['error' => 'Bill not found'], 404);
    }
    return response()->json($bill);
}

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric',
            'due_date' => 'required|date'
        ]);

        $validated['user_id'] = Auth::id(); // Securely set user_id

        $bill = Bill::create($validated);
        $this->createNotification("New bill '{$bill->name}' created with amount {$bill->amount} due on {$bill->due_date}.");
        return response()->json($bill, 201);
    }
    
    public function update(Request $request, Bill $bill)
    {
        $bill = Bill::where('id', $bill->id)->where('user_id', Auth::id())->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'amount' => 'required|numeric',
            'due_date' => 'required|date'
        ]);

        $bill->update($validated);
        $this->createNotification("Bill '{$bill->name}' updated to amount {$bill->amount} due on {$bill->due_date}.");
        return response()->json($bill);
    }

    public function destroy(Bill $bill)
    {
        $bill = Bill::where('id', $bill->id)->where('user_id', Auth::id())->firstOrFail();
        
        $bill->delete();
        $this->createNotification("Bill '{$bill->name}' has been deleted.");
        return response()->json(null, 204);
    }
}