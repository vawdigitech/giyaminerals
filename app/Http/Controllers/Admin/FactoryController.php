<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Factory;
use Illuminate\Http\Request;

class FactoryController extends Controller
{
    public function index()
    {
        return view('factories.index', ['factories' => Factory::all()]);
    }

    public function create()
    {
        return view('factories.create');
    }

    public function store(Request $request)
    {
        $request->validate(['name' => 'required']);
        Factory::create($request->only('name', 'location'));
        return redirect()->route('factories.index')->with('success', 'Factory added');
    }

    public function show(Factory $factory)
    {
        return view('factories.show', compact('factory'));
    }

    public function edit(Factory $factory)
    {
        return view('factories.edit', compact('factory'));
    }

    public function update(Request $request, Factory $factory)
    {
        $request->validate(['name' => 'required']);
        $factory->update($request->only('name', 'location'));
        return redirect()->route('factories.index')->with('success', 'Factory updated');
    }

    public function destroy(Factory $factory)
    {
        $factory->delete();
        return redirect()->route('factories.index')->with('success', 'Factory deleted');
    }
}
