@extends('backend.app')

<!-- Title -->
@section('title', 'Forums List')

{{-- Main Content --}}
@section('content')

    <div class="app-content-area">
        <div class="container-fluid">
            <!-- row -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="text-end d-flex mx-3 justify-content-between align-items-center mb-4">
                        <h3>Forums</h3>
                        @can('create forum')
                            <a href="{{ route('admin.furam.create') }}" class="btn btn-primary mb-3">Write a Forum</a>
                        @endcan
                    </div>

                    <div class="mb-6 card mx-3">
                        <!-- Tab content -->
                        <div class="tab-content p-5" id="pills-tabContent-table">
                            <div class="tab-pane tab-example-design fade show active" id="pills-table-design"
                                role="tabpanel" aria-labelledby="pills-table-design-tab">
                                <!-- Basic table -->
                                <div class="table-responsive">
                                    <table id="data-table"
                                        class="table table-striped text-center w-100 display responsive nowrap"
                                        cellspacing="0" width="100%">
                                        <thead>
                                            <tr class="table-dark">
                                                <th>SL#</th>
                                                <th>Title</th>
                                                <th>Image</th>
                                                <th>Categories</th>
                                                <th>Views</th>
                                                <th>Votes</th>
                                                <th>User</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="align-middle">
                                        <tbody>
                                    </table>
                                </div>
                                <!-- Basic table -->
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection
{{-- Add Script --}}
@push('script')
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <!-- sweetalert -->
    <script type="text/javascript" src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.22/js/jquery.dataTables.min.js"
        type="text/javascript"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.10.22/js/dataTables.bootstrap4.min.js"
        type="text/javascript"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/buttons/1.6.5/js/dataTables.buttons.min.js"
        type="text/javascript"></script>
    <script>
        $(document).ready(function() {
            var searchable = [];
            var selectable = [];
            $.ajaxSetup({
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                }
            });
            if (!$.fn.DataTable.isDataTable('#data-table')) {
                let dTable = $('#data-table').DataTable({
                    order: [],
                    lengthMenu: [
                        [25, 50, 100, 200, 500, -1],
                        [25, 50, 100, 200, 500, "All"]
                    ],
                    processing: true,
                    responsive: true,
                    serverSide: true,

                    language: {
                        processing: `<div class="text-center">
                            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status">
                            <span class="visually-hidden">Loading...</span>
                          </div>
                            </div>`
                    },

                    scroller: {
                        loadingIndicator: false
                    },
                    pagingType: "full_numbers",
                    dom: "<'row justify-content-between table-topbar'<'col-md-2 col-sm-4 px-0'l><'col-md-2 col-sm-4 px-0'f>>tipr",
                    ajax: {
                        url: "{{ route('admin.furam.index') }}",
                        type: "get",
                    },

                    columns: [{
                            data: 'DT_RowIndex',
                            name: 'DT_RowIndex',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'post_title',
                            name: 'post_title',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'feature_image',
                            name: 'feature_image',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'categories',
                            name: 'categories',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'views',
                            name: 'views',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'votes',
                            name: 'votes',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'username',
                            name: 'username',
                            orderable: true,
                            searchable: true
                        },
                        {
                            data: 'action',
                            name: 'action',
                            orderable: true,
                            searchable: true
                        },

                    ],
                });

                dTable.buttons().container().appendTo('#file_exports');

                new DataTable('#example', {
                    responsive: true
                });
            }
        });
        // delete Confirm
        function showDeleteConfirm(id) {
            event.preventDefault();
            swal({
                title: `Are you sure you want to delete this record?`,
                text: "If you delete this, it will be gone forever.",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    deleteItem(id);
                }
            });
        };

        // Delete Button
        function deleteItem(id) {
            var url = '{{ route('admin.forum.destroy', ':id') }}';
            $.ajax({
                type: "DELETE",
                url: url.replace(':id', id),
                success: function(resp) {
                    // Reloade DataTable
                    $('#data-table').DataTable().ajax.reload();
                    if (resp.success === true) {
                        // show toast message
                        toastr.success(resp.message);

                    } else if (resp.errors) {
                        toastr.error(resp.errors[0]);
                    } else {
                        toastr.error(resp.message);
                    }
                }, // success end
                error: function(error) {
                    // location.reload();
                } // Error
            })
        }
    </script>
@endpush
