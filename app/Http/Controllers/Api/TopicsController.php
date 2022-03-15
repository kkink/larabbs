<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Queries\TopicQuery;
use App\Http\Requests\Api\TopicRequest;
use App\Http\Resources\TopicResource;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class TopicsController extends Controller
{
    /**
     * 发布话题
     * @param TopicRequest $request
     * @param Topic $topic
     * @return TopicResource
     */
    public function store(TopicRequest $request, Topic $topic)
    {
        $topic->fill($request->all());
        $topic->user_id = $request->user()->id;
        $topic->save();

        return new TopicResource($topic);
    }

    /**
     * 修改话题
     * @param TopicRequest $request
     * @param Topic $topic
     * @return TopicResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(TopicRequest $request, Topic $topic)
    {
        // 修改权限检测
        $this->authorize('update', $topic);

        $topic->update($request->all());

        return new TopicResource($topic);
    }

    /**
     * 删除话题
     * @param Topic $topic
     * @return TopicResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy(Topic $topic)
    {
        // 删除权限检测
        $this->authorize('destroy', $topic);

        $topic->delete();

        return response(null, 204);
    }

    /**
     * 话题列表
     * @param Request $request
     * @param Topic $topic
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, TopicQuery $query)
    {
//        $query = $topic->query();
//
//        // 如果传了分类 ID ,则加入 where 条件中
//        if ($categoryId = $request->category_id) {
//            $query->where('category_id',$categoryId);
//        }

//        $topics = $query
//            ->with('user','category')
//            ->withOrder($request->order)
//            ->paginate();

        // 使用 laravel-query-builder 组件后
        $topics = $query->paginate();

        return TopicResource::collection($topics);
    }

    /**
     * 某个用户的话题列表
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function userIndex(Request $request, User $user, TopicQuery $query)
    {
//        $query = $user->topics()->getQuery();
//
//        $topics = QueryBuilder::for($query)
//            ->allowedIncludes('user', 'category')
//            ->allowedFilters([
//                'title',
//                // 精确过滤器
//                AllowedFilter::exact('category_id'),
//                AllowedFilter::scope('withOrder')->default('recentReplied'),
//            ])
//            ->paginate();

        // 优化后的写法
        $topics = $query->where('user_id',$user->id)->paginate();

        return TopicResource::collection($topics);
    }

    /**
     * 话题详情
     * @param $topicId
     * @return TopicResource
     */
    public function show($topicId, TopicQuery $query)
    {
        $topics = $query->findOrFail($topicId);
        return new TopicResource($topics);
    }
}
