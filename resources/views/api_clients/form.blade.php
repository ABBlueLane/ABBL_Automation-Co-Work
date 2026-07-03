@php
    $selectedStatus = old('status', $apiClient?->status ?? 'active');
@endphp

<div class="field">
    <label for="version">Version / Name</label>
    <input id="version" name="version" value="{{ old('version', $apiClient?->version) }}" maxlength="255" required>
</div>

<div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description">{{ old('description', $apiClient?->description) }}</textarea>
</div>

<div class="field">
    <label for="status">Status</label>
    <select id="status" name="status" required>
        <option value="active" @selected($selectedStatus === 'active')>active</option>
        <option value="inactive" @selected($selectedStatus === 'inactive')>inactive</option>
    </select>
</div>

<div class="actions">
    <button type="submit">Save</button>
    <a href="{{ route('api_clients.index') }}">Cancel</a>
</div>
