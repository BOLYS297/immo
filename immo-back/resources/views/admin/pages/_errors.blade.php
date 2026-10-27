@if($errors->any())
    <div class="alert alert-danger border-0 mb-4" role="alert">
        <ul class="mb-0 ps-3 small">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
