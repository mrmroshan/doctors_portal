@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="flex items-center justify-center min-h-screen bg-gray-100">
        <div class="w-full max-w-md p-8 space-y-8 bg-white rounded-lg shadow-md">
            <h1 class="text-2xl font-bold text-center">Login to Odoo</h1>
            <form action="{{ route('login') }}" method="POST">
                @csrf
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700">Username:</label>
                    <input type="text" id="username" name="username" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password:</label>
                    <input type="password" id="password" name="password" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <button type="submit" class="w-full mt-4 bg-indigo-600 text-white font-bold py-2 rounded-md hover:bg-indigo-700">Login</button>
            </form>
        </div>
    </div>
@endsection
