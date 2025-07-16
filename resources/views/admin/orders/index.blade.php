@extends('layouts.admin')

@section('title', 'Admins')

@push('vendor-styles')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/datatables.css') }}">
@endpush

@push('page-styles')
    <style>

        .center{
            display: flex;
            justify-content: center;
        }
        .card-header {
            padding: 11% !important;
        }

        /* Custom styles for the modal */
        .modal-dialog {
            max-width: 400px;
        }

        .modal-content {
            border-radius: 10px;
        }

        .modal-header {
            border-top-left-radius: 10px;
            border-top-right-radius: 10px;
            background-color: #f0f0f0;
            border-bottom: none;
        }

        .modal-title {
            color: #333;
        }

        .modal-body {
            padding: 20px;
            color: #555;
        }

        .modal-footer {
            border-top: none;
        }

        .close {
            color: #555;
            opacity: 0.8;
        }

        .close:hover {
            opacity: 1;
        }

        /* Add some custom styles for user information */
        .user-info {
            margin-bottom: 10px;
        }

        .user-info strong {
            display: block;
            color: #333;
        }

        .user-info span {
            color: #555;
        }
    </style>
@endpush

@section('body')
    <div class="container-fluid">
        <div class="page-title">
            <div class="row">
                <div class="col-12 col-sm-6">
                    <h3>Orders List</h3>
                </div>
                <div class="col-12 col-sm-6">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"> <a class="home-item" href="{{ route('admin.dashboard.index') }}"><i
                                    data-feather="home"></i></a></li>
                        <li class="breadcrumb-item">Orders</li>
                        <li class="breadcrumb-item active">View Orders</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" value="{{$status}}" id="order_status">


    <!-- Container-fluid starts-->
    <div class="container-fluid crypto-dash">
        <div class="row">
            
            <div class="col-xl-3 col-md-6 dash-lg-50">
                 <a href="{{route('admin.order.orders',['status'=>'all']) }}">
                <div class="card crypto-chart overflow-hidden">
                    <div class="card-header card-no-border">
                        <div class="media">
                            <div class="media-body">
                                <h1 class="font-primary">Total Orders</h1>
                            </div>
                            <div class="media-end">
                                <h1>{{ $total_orders }}</h1>

                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="crypto-dashborad-chart">
                            <div id="bitcoin-chart"></div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 dash-lg-50">
            <a href="{{route('admin.order.orders',['status'=>2]) }}">
                <div class="card crypto-chart overflow-hidden">
                    <div class="card-header card-no-border">
                        <div class="media">
                            <div class="media-body">
                                <h1 class="font-info">Accepted Orders</h1>
                            </div>
                            <div class="media-end">
                                <h1>{{ $accept_orders }}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="crypto-dashborad-chart">
                            <div id="ethereum-chart"></div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 dash-lg-50">
             <a href="{{ route('admin.order.orders',['status'=>3]) }}">
                <div class="card crypto-chart overflow-hidden">
                    <div class="card-header card-no-border">
                        <div class="media">
                            <div class="media-body">
                                <h1 class="font-info">Completed Orders</h1>
                            </div>
                            <div class="media-end">
                                <h1>{{ $complete_orders }}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="crypto-dashborad-chart">
                            <div id="ethereum-chart"></div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-xl-3 col-md-6 dash-lg-50">
             <a href="{{ route('admin.order.orders',['status'=>1]) }}">
                <div class="card crypto-chart overflow-hidden">
                    <div class="card-header card-no-border">
                        <div class="media">
                            <div class="media-body">
                                <h1 class="font-secondary">Pending Orders</h1>
                            </div>
                            <div class="media-end">
                                <h1>{{ $pending_orders }}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="crypto-dashborad-chart">
                            <div id="ripple-chart"></div>
                        </div>
                    </div>
                </div>
                </a>
            </div>


            <div class="col-xl-3 col-md-6 dash-lg-50">
             <a href="{{ route('admin.order.orders',['status'=>'past_due_order']) }}">
                <div class="card crypto-chart overflow-hidden">
                    <div class="card-header card-no-border">
                        <div class="media">
                            <div class="media-body">
                                <h1 class="font-secondary">Past due orders</h1>
                            </div>
                            <div class="media-end">
                                <h1>{{isset($past_due_job) ? $past_due_job : '0' }}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="crypto-dashborad-chart">
                            <div id="ripple-chart"></div>
                        </div>
                    </div>
                </div>
                </a>
            </div>

        </div>
    </div>

    <div class="container-fluid user-card">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="display datatables" id="orders-datatable">

                                <thead>
                                    <tr>
                                        <th>S.No</th>
                                        <th>Order Id</th>
                                        <th>Order Date</th>
                                        <th class="center" >Username</th>     
                                        <th>Service</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="modal fade" id="userModal" tabindex="-1" role="dialog" aria-labelledby="userModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="userModalLabel">User Information</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="userModalBody">
                <!-- User information will be populated here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary close" id="close" aria-label="Close">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('vendor-scripts')
    <script src="{{ asset('assets/js/datatable/datatables/jquery.dataTables.min.js') }}"></script>
@endpush

@push('page-scripts')
    <script>
        $(function() {
            var status=$("#order_status").val();
            var i = 1;
            var url = "{{ route('admin.order.orders', ":id") }}";
           url = url.replace(':id', status);
            var table = $('#orders-datatable').DataTable({
                processing: true,
                serverSide: true,
                ajax:url,
                columns: [{
                        "render": function() {
                            return i++;
                        }
                    },
                    {
                        data: 'order_id',
                        name: 'order_id',
                          orderable: true, searchable: true
                    },
                    {
                        data: 'date',
                        name: 'date',
                          orderable: true, searchable: true
                    },
                    {
                        data: 'username',
                        name: 'username',
                        orderable: true, searchable: true
                    },
                    {
                        data: 'category',
                        name: 'category',
                        orderable: true, searchable: true
                    },
                    {
                        data: 'provider',
                        name: 'provider',
                        orderable: true, searchable: true
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: true, searchable: true
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ]
            });

        });

        $(document).on('click', "#username-btn", function() {
                var userId = $(this).data('id');
                $.ajax({
                    url: 'user-info/' + userId,
                    method: 'GET',
                   success: function(response) {
                        // Update the modal body with the user information
                        var userHtml = '<strong>Name:</strong> ' + response.first_name + ' ' + response.last_name + '<br>' +
                            '<strong>Email:</strong> ' + response.email + '<br>' +
                            '<strong>Address:</strong> ' + response.address + '<br>';
                        $('#userModalBody').html(userHtml);
                        $('#userModal').modal('show');
                    },
                });
        });

        $(document).on('click', '.close', function() {
            $('#userModal').modal('hide');
        });
    </script>
@endpush
