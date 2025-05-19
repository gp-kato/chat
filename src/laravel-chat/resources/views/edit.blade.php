@extends('layout')

@section('title', 'EditngGroup')

@section('content')
    <div class="content wrapper">
        <ul>
            <div class="container">
                <h1>グループを編集</h1>
                <form action="{{ route('update', $group->id) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div>
                        <label for="name">グループ名</label>
                        <input type="text" name="name" id="name" value="{{ old('name', $group->name) }}" required>
                    </div>
                    <div>
                        <label for="description">説明文</label>
                        <input type="text" name="description" id="description" value="{{ old('description', $group->description) }}" required>
                    </div>
                    <button type="submit">グループを更新</button>
                </form>
            </div>
        </ul>
    </div>
@endsection
