<style>
    .pac-container {
        z-index: 1051 !important;
    }

    .fa-clipboard:before {
        content: "" none;
    }

    .btn-green {
        background: #24B550;
        border: 1px solid #24B550;
    }
</style>

<form class="needs-validation mt-3" id="add-card-form" action="{{route('admin.recurring-jobs.single-scheduledate')}}" method="post">
    @csrf
    <div class="card">
        <div class="card-body px-4 border">
            <div class="row g-3">
                <div class="col-xl-5 col-12 mt-xl-4">
                    <label class="form-label fw-bold">Change Schdeule Date<span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="validationCustom04" name="dob" required>
                    <div class="invalid-feedback">
                        Please provide a date.
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer px-0 border-0 text-center">
            <input type="hidden" name="job_id" value="{{$job->id}}">
            <input type="hidden" name="user_id" value="{{$job->user_id}}">
            <input type="hidden" name="provider_id" value="{{$job->assigned_to}}">
            <input type="hidden" name="date" value="{{$job->date}}">
            <button class="btn btn-green w-50" type="submit">Schedule Date</button>
        </div>
    </div>
</form>
<script>
    var today = new Date();

    // Format the date to YYYY-MM-DD
    var dd = String(today.getDate()).padStart(2, '0');
    var mm = String(today.getMonth() + 1).padStart(2, '0'); // January is 0!
    var yyyy = today.getFullYear();

    today = yyyy + '/' + mm + '/' + dd;

    // Set the value of the date input field
    document.getElementById("validationCustom04").value = today;
    $('#add-card-form').on('submit', function(e) {
        e.preventDefault();
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            url: "{{ route('admin.recurring-jobs.single-scheduledate') }}",
            type: 'post',
            data: $(this).serialize(),
            success: function(res) {
                console.log(res.message,"dsfdsgsg res");
                if (res && res.success === true) {
                    successMessage(res.message);
                    setTimeout(function() {
                        // Reload the window after 5 seconds
                        location.reload();
                    }, 1000);
                } else if (res && res.success === false) {
                    errorMessage(res.message);
                    // $('#card-save-btn').prop('disabled', false);
                }
            },
            error: function(err) {
                console.log(err);
                errorMessage(err.error);
                // $('#card-save-btn').prop('disabled', false); 
            }
        })
    })
</script>