<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
// use Spatie\Activitylog\Traits\LogsActivity;

class UserTitleTypes extends Model
{
    // use LogsActivity;
    protected $fillable = [
        'label',
    ];

    protected $table = 'user_title_types';

    protected static $logAttributes = ['*'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'user-title-types';

    protected $hidden = [
        'pivot'
    ];

}
