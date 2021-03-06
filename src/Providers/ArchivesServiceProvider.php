<?php

namespace wh1110000\CmsL8\Providers;

use Doctrine\Inflector\InflectorFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;
use wh1110000\CmsL8\Console\Install;
use Doctrine\Inflector\Rules\Patterns;
use Doctrine\Inflector\Rules\Ruleset;
use Doctrine\Inflector\Rules\Substitution;
use Doctrine\Inflector\Rules\Substitutions;
use Doctrine\Inflector\Rules\Transformations;
use Doctrine\Inflector\Rules\Word;
use wh1110000\CmsL8\Providers\AbstractServiceProvider;

/**
 * Class ArchivesServiceProvider
 * @package Workhouse\Archives\Providers
 */

class ArchivesServiceProvider extends AbstractServiceProvider {

	/**
	 *
	 */

	public function loadCommands() {

		if ($this->app->runningInConsole()) {

			$this->commands([
				Install::class,
			]);
		}
	}

	/**
	 *
	 */

	public function loadPublish() {

		foreach(self::getArchives() as $slug){

			$this->publishes([
				$this->dir('/resources/views/website/archives') => resource_path($this->dir('views/vendor/'.$slug, false))
			], $slug);
		}
	}

	/**
	 *
	 */

	public function loadTranslations(){

		foreach(self::getArchives() as $slug){

			$this->loadTranslationsFrom( $this->dir('/resources/lang'), $slug );

			$this->loadTranslationsFrom( $this->dir('/resources/lang'), Str::singular($slug).'-categories' );
		}

		$this->loadTranslationsFrom( $this->dir('/resources/lang'), 'archives' );
	}

	/**
	 *
	 */

	public function loadViews(){

		foreach(self::getArchives() as $slug => $model){

			foreach (['admin', 'website'] as $namespace){

				$views = Finder::create()->in($this->dir('/resources/views/'.$namespace.'/'))->name('*.php');

				foreach($views as $view){

					$path = $view->getPath();

					$postNamespace = app('DoctrineInflector')->singularize(Str::contains($path, 'category') ? Str::singular($slug) . '-category' : $slug);

					$this->loadViewsFrom($path, ($namespace == 'admin' && !Str::endsWith($path, '/admin')) && Str::contains($path, ['admin']) ? $namespace.'.'.$postNamespace : $postNamespace);
				}
			}
		}
	}

	/**
	 * @return array
	 */

	public static function getArchives(){

		$models =  [];

		//if(Schema::hasTable((new \Page)->getTable())) {
		if(Schema::hasTable('pages')) {

			foreach ( \DB::connection( 'mysql' )->select( "SELECT `model`, `type` FROM `pages` where `package` = 'archive' AND `type` = 'archive'" ) as $model ) {

				$models[ $model->model ] = $model->model;

			}
		}

		return $models;
	}


	public static function getWebRoutes(){

		$models =  [];

		//if(Schema::hasTable((new \Page)->getTable())) {
		if(Schema::hasTable('pages')) {

			foreach ( \DB::connection( 'mysql' )->select( "SELECT `model`, `type`, `link` FROM `pages` where `package` = 'archive'" ) as $model ) {

				$models[ $model->type ][] =	[$model->model => $model->link];

			}
		}

		return $models;
	}

	/**
	 * @param null $path
	 *
	 * @return array
	 */

	public function getModels($path = null) {

		$models = [];

		$archives = [
			'Archive',
			'ArchiveCategory',
			'ArchiveArchiveCategory',
			'ArchiveArchiveCountry'
		];

		foreach($archives as $model){

			foreach(array_values(self::getArchives()) as $archive){

				$models[] = str_replace('Archive', $archive, $model);
			}
		}
		/*foreach($archives as $model){

			$models[] = $model;

		}*/

		return $models;
	}

	public function getModelNamespace( $name ) {



			foreach ([
				config( 'general.model_namespace' ),
				$this->namespace . '\\Presenters\\',
				$this->namespace . '\\Models\\',
				'Workhouse\\Archives\\Presenters\\',
				'Workhouse\\Archives\\Models\\'
			] as $_namespace ) {

				if (class_exists($model = Str::finish($_namespace, '\\' ) . $name ) ) {

					return $model;

				} else {

					$modelArray = array_filter(preg_split('/(?=[A-Z])/',$name));

					$modelName = Arr::first($modelArray);

					if($modelName){

						$name =  str_replace($name, 'Archive', $name);

						foreach($modelArray as &$value){

							if($value == $modelName){

								$value = $name;
							}
						}

						$name = implode('', $modelArray);


						if(class_exists($model = Str::finish($_namespace, '\\' ) . $name )){

							return $model;
						}
					}
				}
			}

			//return new Exception('Model '.$name.' not exists');


		//return parent::getModelNamespace( $name ); // TODO: Change the autogenerated stub
	}

	/**
	 *
	 */

	public function loadBreadcrumb() {

		/*Breadcrumbs::for('news.index', function ($trail) {
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.resource-centre'), route('news.index'));
		});

		Breadcrumbs::for('news.show', function ($trail, $article) {
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.resource-centre'), route('page.show', 'resource-centre'));
			$trail->push(  $article, route('news.show', $article ));
		});

		$caseStudy = \Article::type('CaseStudy');

		Breadcrumbs::for('case-studies.show', function ($trail, $articleSlug = null) use ($caseStudy) {
			$article = $caseStudy->where( $caseStudy->getRouteKeyName(), $articleSlug)->first();
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.case-studies'), route('case-study.index'));
			$trail->push(  $article->title, route('case-study.show', $article));
		});

		Breadcrumbs::for('contact.index', function ($trail) {
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.contact'), route('contact.index'));
		});

		Breadcrumbs::for('industry-news.index', function ($trail) {
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.industry-news'), route('industry-news.index'));
		});

		$industryNews = \Article::type('IndustryNews');

		Breadcrumbs::for('industry-news.show', function ($trail, $articleSlug = null) use ($industryNews) {
			$article = $industryNews->where( $industryNews->getRouteKeyName(), $articleSlug)->first();
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.industry-news'), route('industry-news.index'));
			$trail->push(  $article->title, route('industry-news.show', $article));
		});

		Breadcrumbs::for('expert-comment.index', function ($trail) {
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.expert-comment'), route('expert-comment.index'));
		});

		$expertComment = \Article::type('ExpertComment');

		Breadcrumbs::for('expert-comment.show', function ($trail, $articleSlug) use ($expertComment) {
			$article = $expertComment->where( $expertComment->getRouteKeyName(), $articleSlug)->first();
			$trail->push(  __('general.home'), route('page.show', ['homepage']));
			$trail->push(  __('general.expert-comments'), route('expert-comment.index'));
			$trail->push(  $article->title, route('expert-comment.show', $article));
		});*/
	}
}
