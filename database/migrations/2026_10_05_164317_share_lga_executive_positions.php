<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('executive_assignments', function (Blueprint $table) {
            $table->unsignedBigInteger('scope_key')->default(0);
            $table->unique(['executive_position_id', 'scope_key'], 'executive_position_scope');
        });
        Schema::table('executive_assignments', fn (Blueprint $table) => $table->dropUnique(['executive_position_id']));

        DB::transaction(function (): void {
            $canonical = [];
            foreach (DB::table('executive_positions')->where('level', 'lga')->orderBy('id')->get() as $position) {
                $positionId = $canonical[$position->name_key] ?? $position->id;
                DB::table('executive_assignments')->where('executive_position_id', $position->id)->update([
                    'executive_position_id' => $positionId, 'scope_key' => $position->lga_id,
                ]);
                if ($positionId !== $position->id) {
                    DB::table('executive_positions')->where('id', $position->id)->delete();
                } else {
                    $canonical[$position->name_key] = $positionId;
                    DB::table('executive_positions')->where('id', $positionId)->update(['location_key' => 0, 'lga_id' => null]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $lgaIds = DB::table('lgas')->orderBy('id')->pluck('id');
            foreach (DB::table('executive_positions')->where('level', 'lga')->get() as $position) {
                foreach ($lgaIds as $index => $lgaId) {
                    $data = (array) $position;
                    unset($data['id']);
                    $data['location_key'] = $lgaId;
                    $data['lga_id'] = $lgaId;
                    if ($index === 0) {
                        DB::table('executive_positions')->where('id', $position->id)->update($data);
                        $positionId = $position->id;
                    } else {
                        $positionId = DB::table('executive_positions')->insertGetId($data);
                    }
                    DB::table('executive_assignments')->where('executive_position_id', $position->id)->where('scope_key', $lgaId)->update(['executive_position_id' => $positionId]);
                }
                if ($lgaIds->isEmpty()) {
                    throw new RuntimeException('Cannot restore location-specific LGA positions without any LGAs.');
                }
            }
        });
        Schema::table('executive_assignments', fn (Blueprint $table) => $table->unique('executive_position_id'));
        Schema::table('executive_assignments', function (Blueprint $table) {
            $table->dropUnique('executive_position_scope');
            $table->dropColumn('scope_key');
        });
    }
};
