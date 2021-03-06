<?php

foreach(\wh1110000\CmsL8\Providers\ArchivesServiceProvider::getWebRoutes()['archive'] ?? [] as $archives){

	foreach($archives as $model => $slug){

		$as = strtolower(app('DoctrineInflector')->singularize($model));

		Route::as($as.'.')->prefix($slug)->group(function() use ($model) {

			Route::get( '/', [
				'uses' => $model.'Controller@index',
				'as'   => 'index'
			]);
		});
	}
}

foreach(\wh1110000\CmsL8\Providers\ArchivesServiceProvider::getWebRoutes()['post'] ?? [] as $posts){

	foreach($posts as $model => $slug){

		$as = strtolower(app('DoctrineInflector')->singularize($model));

		Route::as($as.'.')->prefix($slug)->group(function() use ($model, $as) {

			Route::get( '/{'.$as.'}', [
				'uses' => $model.'Controller@show',
				'as'   => 'show'
			]);
		});
	}
}