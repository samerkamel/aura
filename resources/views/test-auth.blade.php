<!DOCTYPE html>
<html>
<head>
    <title>Test Authorization</title>
</head>
<body>
    <h1>Authorization Test</h1>

    @can('manage-permission-overrides')
        <p>User CAN manage permission overrides</p>
        <button>Add Extra Permission</button>
    @endcan

    @cannot('manage-permission-overrides')
        <p>User CANNOT manage permission overrides</p>
    @endcannot

    <p>User role: {{ auth()->user()->role ?? 'No role' }}</p>
</body>
</html>
