<?php

namespace Abs\HelperPkg;
use App\Http\Controllers\Controller;

class ThemeDemoController extends Controller {
	/**
	 * Create a new controller instance.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->data['theme'] = config('custom.theme');
	}

	/**
	 * Show the application dashboard.
	 *
	 * @return \Illuminate\Contracts\Support\Renderable
	 */
	public function home() {
		return view($this->data['theme'] . '-pkg::demo/authed-angular-page', $this->data);
	}
}
