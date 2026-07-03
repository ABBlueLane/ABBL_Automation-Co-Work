@csrf

<div class="row">
    <div class="col-md-6 field">
        <label for="first_name">First name</label>
        <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $user->first_name ?? '') }}" required>
        @error('first_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 field">
        <label for="last_name">Last name</label>
        <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $user->last_name ?? '') }}" required>
        @error('last_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 field">
        <label for="nick_name">Nickname</label>
        <input type="text" id="nick_name" name="nick_name" value="{{ old('nick_name', $user->nick_name ?? '') }}">
        @error('nick_name')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 field">
        <label for="phone_no">Phone</label>
        <input type="text" id="phone_no" name="phone_no" value="{{ old('phone_no', $user->phone_no ?? '') }}">
        @error('phone_no')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-8 field">
        <label for="email">Email</label>
        <input type="email" id="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4 field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach (['active' => 'Active', 'inactive' => 'Inactive'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $user->status ?? 'active') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>
</div>

<div class="row">
    <div class="col-md-6 field">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" {{ isset($user) ? '' : 'required' }}>
        @isset($user)
            <div class="text-muted small mt-1">ปล่อยว่างไว้ถ้าไม่ต้องการเปลี่ยนรหัสผ่าน</div>
        @endisset
        @error('password')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6 field">
        <label for="password_confirmation">Confirm password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" {{ isset($user) ? '' : 'required' }}>
    </div>
</div>

<div class="actions">
    <button type="submit">Save</button>
    <a class="button secondary" href="{{ route('users.index') }}">Back</a>
</div>
