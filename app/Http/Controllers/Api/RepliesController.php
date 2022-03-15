<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ReplyRequest;
use App\Http\Resources\ReplyResource;
use App\Models\Reply;
use App\Models\Topic;
use Illuminate\Http\Request;

class RepliesController extends Controller
{

    /**
     * 话题回复
     * @param ReplyRequest $request
     * @param Topic $topic
     * @param Reply $reply
     * @return ReplyResource
     */
    public function store(ReplyRequest $request, Topic $topic, Reply $reply)
    {
        $reply->content = $request->content;
        $reply->topic()->associate($topic);
        $reply->user()->associate($request->user());
        $reply->save();

        return new ReplyResource($reply);
    }

    /**
     * 删除回复
     * @param Topic $topic
     * @param Reply $reply
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Topic $topic, Reply $reply)
    {
        // 如果回复的话题 ID 和对应的话题 ID 不一致
        if ($reply->topic_id != $topic->id) {
            abort(404);
        }

        // 权限策略
        $this->authorize('destroy',$reply);
        $reply->delete();

        return response(null,204);
    }
}
