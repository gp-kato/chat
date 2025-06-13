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
                <hr>
                <table>
                    <thead>
                        <tr>
                            <th>ユーザー</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr>
                                <td>
                                    <p>{{ $user->name }}</p>
                                </td>
                                <td>
                                    <form action="{{ route('remove', ['group' => $group->id, 'user' => $user->id]) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger">退会</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </ul>
    </div>
@endsection
