<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LearningController extends Controller
{
    public function index()
    {
        return view('learning.index');
    }

    public function topic($slug)
    {
        $viewMap = [
            'basics'          => 'learning.topics.basics',
            'inventory-adhoc' => 'learning.topics.inventory-adhoc',
            'playbooks'       => 'learning.topics.playbooks',
            'roles'           => 'learning.topics.roles',
        ];

        if (!array_key_exists($slug, $viewMap)) {
            abort(404, 'Learning topic not found.');
        }

        return view($viewMap[$slug]);
    }
}
