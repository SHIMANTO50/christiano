<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class System Setting
 *
 * @package App\Models
 * @property int    $id
 * @property int    $user_id
 * @property string $key
 * @property mixed  $value
 * @method static Builder|Setting byUser($user_id = null)
 * @method static Builder|Setting systemOnly()
 */
class SystemSetting extends Model
{
    public $table = 'system_settings';

    public $timestamps = false;

    public $fillable = [
        'user_id',
        'system_key',
        'value',
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];

    /*
     | ========================================================================
     | SCOPES
     */

    /**
     * Scope for the user relation
     *
     * @param Builder  $query
     * @param int|null $user_id
     * @return Builder
     */
    public function scopeByUser(Builder $query, int $user_id = null)
    {
        if (is_null($user_id) && auth()->check()) {
            $user_id = auth()->id();
        }
        return $query->where('user_id', $user_id);
    }

    /**
     * Scope to get system settings only
     *
     * @param Builder $query
     * @return Builder
     */
    public function scopeSystemOnly(Builder $query)
    {
        return $query->whereNull('user_id');
    }
}
