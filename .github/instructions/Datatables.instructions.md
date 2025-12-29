# QFlow DataTables Standardization Guide

## Overview

This document establishes standards for implementing DataTables in the QFlow system, ensuring a consistent, responsive, and accessible user experience across all interfaces. These standards align with the Vuexy Admin Template design patterns and best practices.

## HTML Structure

### Basic Layout

```html
<!-- Card wrapper for DataTable -->
<div class="card">
  <!-- Card header with title and actions -->
  <div class="card-header d-flex justify-content-between align-items-center">
    <div>
      <h5 class="card-title mb-0">Table Title</h5>
      <p class="mb-0">Optional description text</p>
    </div>
    <div>
      <!-- Primary action button(s) -->
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewModal">
        <i class="ti tabler-plus me-sm-1"></i>
        <span class="d-none d-sm-inline-block">Add New</span>
      </button>
    </div>
  </div>

  <!-- Optional: Filter Row (if needed) -->
  <div class="card-body pb-0" id="filterSection">
    <form id="filterForm" class="row g-3">
      <div class="col-md-3">
        <label class="form-label">Filter Label</label>
        <select class="form-select dt-filter" data-column="1">
          <option value="">All</option>
          <!-- Options here -->
        </select>
      </div>
      <!-- Add more filters as needed -->
      <div class="col-md-2 align-self-end">
        <button type="button" class="btn btn-primary w-100" id="applyFilters">Apply</button>
      </div>
      <div class="col-md-2 align-self-end">
        <button type="reset" class="btn btn-label-secondary w-100" id="clearFilters">Clear</button>
      </div>
    </form>
  </div>

  <!-- DataTable container -->
  <div class="card-datatable table-responsive">
    <table class="datatables-example table border-top">
      <thead>
        <tr>
          <!-- Define all column headers -->
          <th>Column 1</th>
          <th>Column 2</th>
          <!-- Actions column should typically be last -->
          <th class="text-center">Actions</th>
        </tr>
      </thead>
    </table>
  </div>
</div>
```

### Actions Column Layout

```html
<td class="text-center">
  <div class="d-flex justify-content-center align-items-center">
    <!-- View action -->
    <a
      href="javascript:;"
      class="text-body view-record"
      data-id="{{ $record->id }}"
      data-bs-toggle="tooltip"
      data-bs-placement="top"
      title="View">
      <i class="ti tabler-eye ti-sm me-2"></i>
    </a>

    <!-- Edit action -->
    <a
      href="javascript:;"
      class="text-body edit-record"
      data-id="{{ $record->id }}"
      data-bs-toggle="tooltip"
      data-bs-placement="top"
      title="Edit">
      <i class="ti tabler-edit ti-sm me-2"></i>
    </a>

    <!-- Delete action -->
    <a
      href="javascript:;"
      class="text-body delete-record"
      data-id="{{ $record->id }}"
      data-name="{{ $record->name }}"
      data-bs-toggle="tooltip"
      data-bs-placement="top"
      title="Delete">
      <i class="ti tabler-trash ti-sm mx-2"></i>
    </a>
  </div>
</td>
```

## JavaScript Configuration

### Standard Initialization

```javascript
// Initialize DataTable with standardized configuration
const dataTable = document.querySelector('.datatables-example');

if (dataTable) {
  // Destroy any existing DataTable instance first
  if ($.fn.dataTable.isDataTable(dataTable)) {
    $(dataTable).DataTable().destroy();
  }

  const table = $(dataTable).DataTable({
    // Processing indicator
    processing: true,

    // Responsive layout setup
    responsive: {
      details: {
        display: $.fn.dataTable.Responsive.display.modal({
          header: function (row) {
            const data = row.data();
            return 'Details of ' + data[0]; // First column usually contains name
          }
        }),
        type: 'column',
        renderer: function (api, rowIdx, columns) {
          const data = $.map(columns, function (col, i) {
            return col.title !== ''
              ? '<tr data-dt-row="' +
                  col.rowIndex +
                  '" data-dt-column="' +
                  col.columnIndex +
                  '">' +
                  '<td>' +
                  col.title +
                  '</td>' +
                  '<td>' +
                  col.data +
                  '</td>' +
                  '</tr>'
              : '';
          }).join('');
          return data ? $('<table class="table"/><tbody />').append(data) : false;
        }
      }
    },

    // DOM Structure for controls
    dom: '<"row mx-2"<"col-md-2"<"me-3"l>><"col-md-10"<"dt-action-buttons text-xl-end text-lg-start text-md-end text-start d-flex align-items-center justify-content-end flex-md-row flex-column mb-3 mb-md-0"fB>>>t<"row mx-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',

    // Language customization
    language: {
      searchPlaceholder: 'Search...',
      search: '',
      paginate: {
        previous: '&nbsp;',
        next: '&nbsp;'
      }
    },

    // Column definitions
    columnDefs: [
      // Configure searchability, ordering, and visibility for each column
      { orderable: true, targets: 0 },
      { orderable: true, targets: 1 },
      // Actions column should not be searchable or orderable
      { orderable: false, searchable: false, targets: -1 }
    ],

    // Export buttons configuration
    buttons: [
      {
        extend: 'collection',
        className: 'btn btn-label-secondary dropdown-toggle mx-3',
        text: '<i class="ti tabler-file-export me-1"></i><span class="d-none d-sm-inline-block">Export</span>',
        buttons: [
          {
            extend: 'print',
            text: '<i class="ti tabler-printer me-2"></i>Print',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' } // Exclude actions column
          },
          {
            extend: 'csv',
            text: '<i class="ti tabler-file-text me-2"></i>CSV',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          },
          {
            extend: 'excel',
            text: '<i class="ti tabler-file-spreadsheet me-2"></i>Excel',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          },
          {
            extend: 'pdf',
            text: '<i class="ti tabler-file-code-2 me-2"></i>PDF',
            className: 'dropdown-item',
            exportOptions: { columns: ':not(:last-child)' }
          }
        ]
      }
    ],

    // Additional options as needed for specific use cases
    ordering: true,
    paging: true,
    info: true,
    lengthMenu: [10, 25, 50, 100]
  });
}
```

## Responsiveness Standards

### Required Dependencies

For proper responsive functionality, include these files:

```php
// In your Blade template
@section('vendor-style')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'
])
@endsection

@section('vendor-script')
@vite([
  'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
  'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.js',
  'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.js'
])
@endsection
```

### Mobile-First Considerations

- Use responsive classes to control column visibility at different breakpoints
- Consider using `priority` in columnDefs to determine which columns collapse first
- Always ensure action buttons remain accessible on mobile devices
- Use tooltips for action buttons to provide context on smaller screens

```javascript
// Example of priority-based responsive columns
columnDefs: [
  { responsivePriority: 1, targets: 0 }, // Name - highest priority, always visible
  { responsivePriority: 3, targets: 1 }, // Medium priority
  { responsivePriority: 4, targets: 2 }, // Low priority, hides early
  { responsivePriority: 2, targets: -1 } // Actions - high priority, visible longer
];
```

## Filters and Search

### Basic Search

The standard configuration includes a global search field. For more specific filtering:

### Column-Specific Filters

```javascript
// Filter handling
$('#applyFilters').on('click', function () {
  table.draw();
});

$('#clearFilters').on('click', function () {
  $('#filterForm')[0].reset();
  table.draw();
});

// Custom filtering function
$.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
  // Get filter values
  const statusFilter = $('#status-filter').val();
  const typeFilter = $('#type-filter').val();

  // Get data from the row
  const status = data[5]; // Column index of status
  const type = data[2]; // Column index of type

  // Apply filters
  let statusMatch = !statusFilter || status.includes(statusFilter);
  let typeMatch = !typeFilter || type === typeFilter;

  // Return true if all filters match
  return statusMatch && typeMatch;
});
```

## Best Practices

### Performance

1. **Lazy Loading for Large Datasets**

   - Use server-side processing for tables with more than 1,000 records
   - Implement pagination server-side to reduce initial load time

2. **Limit Initial Columns**
   - Show only essential columns by default
   - Use responsive display or "View Details" for additional information

### Accessibility

1. **Keyboard Navigation**

   - Ensure all interactive elements are keyboard accessible
   - Use proper ARIA labels for interactive elements

2. **Screen Reader Support**
   - Add `aria-label` attributes to tables
   - Include helpful descriptions for action icons

```html
<table class="datatables-example table" aria-label="Data records for clients"></table>
```

### Error Handling

- Include empty state messages when no data is present
- Add fallback behavior if datatable initialization fails
- Provide clear user feedback for loading states and errors

```html
<!-- Empty state example -->
<tr class="empty-row">
  <td colspan="7" class="text-center">
    <div class="empty-state p-5">
      <img src="/assets/img/empty-table.svg" alt="No data" class="w-25 mb-4" />
      <h5>No records found</h5>
      <p class="mb-4">There are no records available at this time.</p>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addNewModal">
        <i class="ti tabler-plus me-1"></i> Add New Record
      </button>
    </div>
  </td>
</tr>
```

## Integration with Backend

### Laravel Controllers

For server-side processing, set up your controller to return the appropriate format:

```php
public function getRecordsData()
{
    $records = YourModel::with(['relationships'])
        ->select(['id', 'name', 'status', 'created_at', /* other fields */]);

    return DataTables::of($records)
        ->addColumn('actions', function ($record) {
            return view('partials.action-buttons', compact('record'))->render();
        })
        ->rawColumns(['actions'])
        ->toJson();
}
```

### Client-Side Configuration for Server-Side Processing

```javascript
const table = $('.datatables-example').DataTable({
  processing: true,
  serverSide: true,
  ajax: {
    url: '/your-data-url',
    type: 'GET'
  },
  columns: [
    { data: 'name' },
    { data: 'status' },
    { data: 'created_at' },
    { data: 'actions', orderable: false, searchable: false }
  ]
  // Other standard configurations
});
```

## Best Practices Summary

1. **Always include responsive configuration** to ensure tables work well on mobile devices.
2. **Use server-side processing** for tables with large datasets.
3. **Apply proper column definitions** for search, sort, and render functions.
4. **Initialize tooltips** for action buttons.
5. **Include proper error handling** for AJAX operations.
6. **Implement filters above the table** but below the title when needed.
7. **Always include export options** for tables with important data.
8. **Ensure accessibility** by using proper ARIA attributes and keyboard navigation.
9. **Place the "Add New" button at the same level as the title** in the card header.
10. **Maintain consistent styling** across all DataTables in the application.

## Conclusion

Following these standardized patterns ensures a consistent user experience across the QFlow platform, reduces development time, and maintains compatibility with the Vuexy design system. All datatable implementations should adhere to these guidelines to ensure quality and maintainability.
