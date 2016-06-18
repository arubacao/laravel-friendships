<?php
/**
 * This file is part of Friends.
 *
 * (c) Christopher Lass <arubacao@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Arubacao\Friends\Traits;

use Arubacao\Friends\Status;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

trait Friendable
{

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function friends()
    {
        $me = $this->with([
            'friendship_sender' => function ($query) {
                $query->where('status', Status::PENDING)
                    ->orderBy('updated_at', 'desc')
                    ->first()
                ;
            },
            'friendship_recipient' => function ($query) {
                $query->where('status', Status::PENDING)
                    ->orderBy('updated_at', 'desc')
                    ->first()
                ;
            },
        ])
            ->where('id', '=', $this->getKey())
            ->get();

        $friends = collect([]);
        $friends->push($me->friendship_sender);
        $friends->push($me->friendship_recipient);
        $friends = $friends->flatten();

        return $friends;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function any_friends()
    {
        $me = $this->with([
            'friendship_sender',
            'friendship_recipient',
        ])
            ->where('id', '=', $this->getKey())
            ->first();

        $any_friends = collect([]);
        $any_friends->push($me->friendship_sender);
        $any_friends->push($me->friendship_recipient);
        $any_friends = $any_friends->flatten();

        return $any_friends;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friendship_sender()
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'sender_id', 'recipient_id')
            ->withTimestamps()
            ->withPivot([
                'status',
                'deleted_at',
            ]);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function friendship_recipient()
    {
        return $this->belongsToMany(
            self::class,
            'friends',
            'recipient_id', 'sender_id')
            ->withTimestamps()
            ->withPivot([
                'status',
                'deleted_at',
            ]);
    }

    /**
     * Alias to eloquent many-to-many relation's attach() method.
     *
     * @param int|User $user
     * @return $this
     */
    public function sendFriendRequest($user)
    {
        $userId = $this->retrieveUserId($user);

        $this->friendship_sender()->attach($userId, [
            'status' => Status::PENDING,
        ]);

        // Reload relation
        $this->load('friendship_sender');

        return $this;
    }

    /**
     * @param $user
     * @return mixed
     */
    protected function retrieveUserId($user)
    {
        if (is_object($user)) {
            $user = $user->getKey();
        }
        if (is_array($user) && isset($user[ 'id' ])) {
            $user = $user[ 'id' ];
        }

        return $user;
    }
}
