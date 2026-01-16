<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function index()
    {
        return view('sites.index', ['sites' => Site::all()]);
    }

    public function create()
    {
        return view('sites.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        Site::create($request->only('name', 'location'));
        return redirect()->route('sites.index')->with('success', 'Site added');
    }

    public function edit(Site $site)
    {
        return view('sites.edit', compact('site'));
    }

    public function update(Request $request, Site $site)
    {
        $request->validate(['name' => 'required']);
        $site->update($request->only('name', 'location'));
        return redirect()->route('sites.index')->with('success', 'Site updated');
    }

    public function destroy(Site $site)
    {
        $site->delete();
        return redirect()->route('sites.index')->with('success', 'Site deleted');
    }
}
