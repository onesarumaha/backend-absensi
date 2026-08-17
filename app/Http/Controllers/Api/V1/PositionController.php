<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Position\StorePositionRequest;
use App\Http\Requests\Position\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $positions = Position::query()
            ->latest()
            ->paginate(10);

        return PositionResource::collection($positions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePositionRequest $request)
    {
        $position = Position::create(
            $request->validated()
        );

        return new PositionResource($position);
    }

    /**
     * Display the specified resource.
     */
    public function show(Position $position)
    {
        return new PositionResource($position);
    }

    /**
     * Update the specified resource in storage.
     */
      public function update(UpdatePositionRequest $request, Position $position ) 
      {
        $position->update(
            $request->validated()
        );

        return new PositionResource($position);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Position $position)
    {
        if ($position->employees()->exists()) {
            return response()->json([
                'message' => 'Position tidak dapat dihapus karena masih digunakan oleh pegawai.',
            ], 422);
        }

        $position->delete();

        return response()->json([
            'message' => 'Position berhasil dihapus.',
        ]);
    }
}
