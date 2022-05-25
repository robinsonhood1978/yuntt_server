<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\ArticleModel;
use common\models\AcategoryModel;
use common\models\GcategoryModel;
use common\models\NavigationModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id ArticleController.php 2018.7.20 $
 * @author mosir
 */

class ArticleController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'), [
			'navs'	=> NavigationModel::getList()
		]);
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'page']);
		
		// 取得列表数据
		$query = ArticleModel::find()->select('article_id,title,add_time')->where(['if_show' => 1, 'store_id' => 0])->orderBy(['article_id' => SORT_DESC]);
		if($post->cate_id) {
			$allId = AcategoryModel::getDescendantIds($post->cate_id);
			$query->andWhere(['in', 'cate_id', $allId]);
			
			$category = AcategoryModel::find()->select('cate_name')->where(['cate_id' => $post->cate_id, 'if_show' => 1, 'store_id' => 0])->asArray()->one();
		}
		$page = Page::getPage($query->count(), 20);
		$this->params['articles'] = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params['pagination'] = Page::formatPage($page);
		$this->params['category'] = $category;
		
		// 文章分类
        $acategories = AcategoryModel::find()->select('cate_id,cate_name')->where(['parent_id' => 0, 'if_show' => 1])->asArray()->all();
		foreach($acategories as $key => $val) {
			$acategories[$key]['children'] = AcategoryModel::find()->select('cate_id,cate_name')->where(['parent_id' => $val['cate_id'], 'if_show' => 1])->asArray()->all();
		}
		$this->params['acategories'] = $acategories;

		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
		
		$this->params['page'] = Page::seo(['title' => $category ? $category['cate_name'] : Language::get('new_article')]);
        return $this->render('../article.index.html', $this->params);
	}
	
	public function actionView()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$post->id || !($article = ArticleModel::find()->select('article_id,title,link,description,add_time')->where(['article_id' => $post->id, 'if_show' => 1, 'store_id' => 0])->asArray()->one())) {
			return Message::warning(Language::get('no_such_article'));
		}
		
		// 外链文章跳转
		if ($article['link'] && ($article['link'] != Url::toRoute(['article/view', 'id' => $post->id], true))) {
			return $this->redirect($article['link']);
		}
		$this->params['article'] = $article;
		
		// 文章分类
        $acategories = AcategoryModel::find()->where(['parent_id' => 0, 'if_show' => 1, 'store_id' => 0])->asArray()->all();
		foreach($acategories as $key => $val) {
			$acategories[$key]['children'] = AcategoryModel::find()->select('cate_id,cate_name')->where(['parent_id' => $val['cate_id'], 'if_show' => 1])->asArray()->all();
		}
		$this->params['acategories'] = $acategories;

		// 推荐文章
		$this->params['articlelist'] = ArticleModel::find()->orderBy(['article_id' => SORT_DESC])->limit(10)->asArray()->all();
		
		// 上一篇下一篇
 		$this->params['pre_article'] = ArticleModel::find()->select('article_id,title')->where(['<', 'article_id', $post->id])->andWhere(['if_show' => 1, 'store_id' => 0])->orderBy(['article_id' => SORT_DESC])->asArray()->one();
		$this->params['next_article'] = ArticleModel::find()->select('article_id,title')->where(['>', 'article_id', $post->id])->andWhere(['if_show' => 1, 'store_id' => 0])->orderBy(['article_id' => SORT_ASC])->asArray()->one();
		
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
		
		$this->params['page'] = Page::seo(['title' => $article['title']]);
        return $this->render('../article.view.html', $this->params);
	}
}