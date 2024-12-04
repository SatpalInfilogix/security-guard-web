@foreach($securityGuards as $key => $securityGuard)
    <tr>
        <td>{{ ++$key }}</td>
        <td>{{ $securityGuard->surname}}</td>
        <td>{{ $securityGuard->first_name }}</td>
        <td>{{ $securityGuard->middle_name }}</td>
        <td>{{ $securityGuard->email }}</td>
        <td>{{ $securityGuard->phone_number }}</td>
        <td class="action-buttons">
            <a href="{{ route('security-guards.edit', $securityGuard->id)}}" class="btn btn-outline-secondary btn-sm edit"><i class="fas fa-pencil-alt"></i></a>
            <button data-source="Security Guard" data-endpoint="{{ route('security-guards.destroy', $securityGuard->id) }}"
                class="delete-btn btn btn-outline-secondary btn-sm edit">
                <i class="fas fa-trash-alt"></i>
            </button>
        </td>
    </tr>
@endforeach