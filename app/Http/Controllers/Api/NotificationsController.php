<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;

class NotificationsController extends Controller
{
    /**
     * 我的消息通知列表
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        // 用户模型的 notifications 方法是 Laravel 的消息通知系统 为我们提供的方法，按通知创建时间倒叙排序
        $notifications = $request->user()->notifications()->paginate();

        return NotificationResource::collection($notifications);
    }

    /**
     * 我的未读消息数量
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        return response()->json([
            'unread_count' => $request->user()->notification_count,
        ]);
    }

    /**
     * 消息已读
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function read(Request $request)
    {
        $request->user()->markAsRead();

        return response(null,204);
    }
}
