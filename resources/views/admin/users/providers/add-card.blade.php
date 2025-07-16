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

<form class="needs-validation mt-3" id="add-card-form" action="{{route('admin.addStripeAccountProvider')}}" method="post">
    @csrf
    <div class="card">


        <div class="card-body px-4 border">
            <div class="row g-3">
                <div class="col-xl-6 col-12">
                    <label for="validationCustom01" class="div-label fw-bold">SSN Number<span class="text-danger">*</span></label></label>
                    <input placeholder="Enter your ssn last 4 digits" type="text" class="form-control" id="validationCustom01" name="ssn_last_4" required>
                    <!-- {{-- <div class="valid-feedback">
                    Looks good!
                </div> --}} -->
                </div>
                <div class="col-xl-6 col-12">

                    <label for="validationCustom02" class="form-label fw-bold">Routing Number<span class="text-danger">*</span></label></label>
                    <input type="text" placeholder="Enter your routing number" class="form-control" id="validationCustom02" name="routing_number" required>
                    <!-- {{-- <div class="valid-feedback">
                    Looks good!
                </div> --}} -->
                </div>
                <div class="col-xl-7 col-12">
                    <label for="validationCustom03" class="form-label fw-bold">Enter you Account Number
                        <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="validationCustom03" name="account_number" placeholder="Enter your Account Number" required>
                    <!-- <div class="invalid-feedback">
                    Please provide a card number.
                </div> -->
                </div>
                <div class="row g-3">
                    <!-- ... (other form fields) ... -->
                    <div class="col-xl-4 col-12 mt-xl-4">
                        <label class="form-label fw-bold">Country<span class="text-danger">*</span></label>
                        <select class="form-control" id="country" name="country" required disabled>
                            <option value="United States" selected>United States</option>
                            <!-- Add other country options if needed -->
                        </select>
                    </div>
                    <div class="col-xl-4 col-12 mt-xl-4">
                        <label class="form-label fw-bold">State<span class="text-danger">*</span></label>
                        <select style="z-index: 99999999;" class="form-control" id="state" name="state" required>
                            <!-- Options for states will be populated via JavaScript -->
                        </select>
                    </div>
                    <div class="col-xl-4 col-12 mt-xl-4">
                        <label class="form-label fw-bold">City<span class="text-danger">*</span></label>
                        <select class="form-control" id="city" name="city" required disabled title="Select a state first">
                            <option value='' disabled selected>Select a city</option>
                        </select>
                    </div>

                </div>

                <div class="col-xl-4 col-12 mt-xl-4">
                    <label class="form-label fw-bold">Postal Code<span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="postal_code" name="postal_code" placeholder="Enter postal code" required>
                </div>
                <div class="form-group">
                    <label>Address<span class="text-danger">*</span></label>
                    <div class="input-group"><span class="input-group-text">
                            <i class="fa-regular fa-pen-to-square"></i>
                        </span>
                        <input id="reglocation" class="form-control autocomplete-input" type="text" name="address" required="" placeholder="Enter address">
                        <input type="hidden" name="lat" id="reglat">
                        <input type="hidden" name="lng" id="reglng">
                    </div>
                </div>
                <div class="col-xl-5 col-12 mt-xl-4">
                    <label class="form-label fw-bold">Birth Date<span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="validationCustom04" name="dob" required>
                    <div class="invalid-feedback">
                        Please provide a date.
                    </div>
                </div>

            </div>
        </div>

        <div class="card-footer px-0 border-0 text-center">
            <input type="hidden" required name="provider_id" value="{{ $data ?? '' }}">
            <button class="btn btn-green w-50" type="submit">Add Bank</button>
        </div>
    </div>
</form>
<script>
    
    function loadGoogleMapsScript() {
        var script = document.createElement("script");
        script.src = "https://maps.google.com/maps/api/js?key={{ config('google.GOOGLE_MAPS_APIKEY') }}&libraries=places";
        script.type = "text/javascript";
        script.async = true;
        script.defer = true;
        script.addEventListener('load', initializeGoogleMaps);

        document.head.appendChild(script);
    }

    function initializeGoogleMaps() {
        var input = document.getElementById('reglocation');
        var options = {
            componentRestrictions: {
                country: ["us"]
            }
        };
        var autocomplete = new google.maps.places.Autocomplete(input, options);

        autocomplete.addListener('place_changed', function() {
            var place = autocomplete.getPlace();
            document.getElementById('reglat').value = place.geometry.location.lat();
            document.getElementById('reglng').value = place.geometry.location.lng();
        });
    }
    $(document).ready(function() {
        const usStates = [
        "Alabama",
        "Alaska",
        "Arizona",
        "Arkansas",
        "California",
        "Colorado",
        "Connecticut",
        "Delaware",
        "Florida",
        "Georgia",
        "Hawaii",
        "Idaho",
        "Illinois",
        "Indiana",
        "Iowa",
        "Kansas",
        "Kentucky",
        "Louisiana",
        "Maine",
        "Maryland",
        "Massachusetts",
        "Michigan",
        "Minnesota",
        "Mississippi",
        "Missouri",
        "Montana",
        "Nebraska",
        "Nevada",
        "New Hampshire",
        "New Jersey",
        "New Mexico",
        "New York",
        "North Carolina",
        "North Dakota",
        "Ohio",
        "Oklahoma",
        "Oregon",
        "Pennsylvania",
        "Rhode Island",
        "South Carolina",
        "South Dakota",
        "Tennessee",
        "Texas",
        "Utah",
        "Vermont",
        "Virginia",
        "Washington",
        "West Virginia",
        "Wisconsin",
        "Wyoming"
    ];

    const usCities = ["Aberdeen", "Abilene", "Akron", "Albany", "Albuquerque", "Alexandria", "Allentown", "Amarillo", "Anaheim", "Anchorage", "Ann Arbor", "Antioch", "Apple Valley", "Appleton", "Arlington", "Arvada", "Asheville", "Athens", "Atlanta", "Atlantic City", "Augusta", "Aurora", "Austin", "Bakersfield", "Baltimore", "Barnstable", "Baton Rouge", "Beaumont", "Bel Air", "Bellevue", "Berkeley", "Bethlehem", "Billings", "Birmingham", "Bloomington", "Boise", "Boise City", "Bonita Springs", "Boston", "Boulder", "Bradenton", "Bremerton", "Bridgeport", "Brighton", "Brownsville", "Bryan", "Buffalo", "Burbank", "Burlington", "Cambridge", "Canton", "Cape Coral", "Carrollton", "Cary", "Cathedral City", "Cedar Rapids", "Champaign", "Chandler", "Charleston", "Charlotte", "Chattanooga", "Chesapeake", "Chicago", "Chula Vista", "Cincinnati", "Clarke County", "Clarksville", "Clearwater", "Cleveland", "College Station", "Colorado Springs", "Columbia", "Columbus", "Concord", "Coral Springs", "Corona", "Corpus Christi", "Costa Mesa", "Dallas", "Daly City", "Danbury", "Davenport", "Davidson County", "Dayton", "Daytona Beach", "Deltona", "Denton", "Denver", "Des Moines", "Detroit", "Downey", "Duluth", "Durham", "El Monte", "El Paso", "Elizabeth", "Elk Grove", "Elkhart", "Erie", "Escondido", "Eugene", "Evansville", "Fairfield", "Fargo", "Fayetteville", "Fitchburg", "Flint", "Fontana", "Fort Collins", "Fort Lauderdale", "Fort Smith", "Fort Walton Beach", "Fort Wayne", "Fort Worth", "Frederick", "Fremont", "Fresno", "Fullerton", "Gainesville", "Garden Grove", "Garland", "Gastonia", "Gilbert", "Glendale", "Grand Prairie", "Grand Rapids", "Grayslake", "Green Bay", "GreenBay", "Greensboro", "Greenville", "Gulfport-Biloxi", "Hagerstown", "Hampton", "Harlingen", "Harrisburg", "Hartford", "Havre de Grace", "Hayward", "Hemet", "Henderson", "Hesperia", "Hialeah", "Hickory", "High Point", "Hollywood", "Honolulu", "Houma", "Houston", "Howell", "Huntington", "Huntington Beach", "Huntsville", "Independence", "Indianapolis", "Inglewood", "Irvine", "Irving", "Jackson", "Jacksonville", "Jefferson", "Jersey City", "Johnson City", "Joliet", "Kailua", "Kalamazoo", "Kaneohe", "Kansas City", "Kennewick", "Kenosha", "Killeen", "Kissimmee", "Knoxville", "Lacey", "Lafayette", "Lake Charles", "Lakeland", "Lakewood", "Lancaster", "Lansing", "Laredo", "Las Cruces", "Las Vegas", "Layton", "Leominster", "Lewisville", "Lexington", "Lincoln", "Little Rock", "Long Beach", "Lorain", "Los Angeles", "Louisville", "Lowell", "Lubbock", "Macon", "Madison", "Manchester", "Marina", "Marysville", "McAllen", "McHenry", "Medford", "Melbourne", "Memphis", "Merced", "Mesa", "Mesquite", "Miami", "Milwaukee", "Minneapolis", "Miramar", "Mission Viejo", "Mobile", "Modesto", "Monroe", "Monterey", "Montgomery", "Moreno Valley", "Murfreesboro", "Murrieta", "Muskegon", "Myrtle Beach", "Naperville", "Naples", "Nashua", "Nashville", "New Bedford", "New Haven", "New London", "New Orleans", "New York", "New York City", "Newark", "Newburgh", "Newport News", "Norfolk", "Normal", "Norman", "North Charleston", "North Las Vegas", "North Port", "Norwalk", "Norwich", "Oakland", "Ocala", "Oceanside", "Odessa", "Ogden", "Oklahoma City", "Olathe", "Olympia", "Omaha", "Ontario", "Orange", "Orem", "Orlando", "Overland Park", "Oxnard", "Palm Bay", "Palm Springs", "Palmdale", "Panama City", "Pasadena", "Paterson", "Pembroke Pines", "Pensacola", "Peoria", "Philadelphia", "Phoenix", "Pittsburgh", "Plano", "Pomona", "Pompano Beach", "Port Arthur", "Port Orange", "Port Saint Lucie", "Port St. Lucie", "Portland", "Portsmouth", "Poughkeepsie", "Providence", "Provo", "Pueblo", "Punta Gorda", "Racine", "Raleigh", "Rancho Cucamonga", "Reading", "Redding", "Reno", "Richland", "Richmond", "Richmond County", "Riverside", "Roanoke", "Rochester", "Rockford", "Roseville", "Round Lake Beach", "Sacramento", "Saginaw", "Saint Louis", "Saint Paul", "Saint Petersburg", "Salem", "Salinas", "Salt Lake City", "San Antonio", "San Bernardino", "San Buenaventura", "San Diego", "San Francisco", "San Jose", "Santa Ana", "Santa Barbara", "Santa Clara", "Santa Clarita", "Santa Cruz", "Santa Maria", "Santa Rosa", "Sarasota", "Savannah", "Scottsdale", "Scranton", "Seaside", "Seattle", "Sebastian", "Shreveport", "Simi Valley", "Sioux City", "Sioux Falls", "South Bend", "South Lyon", "Spartanburg", "Spokane", "Springdale", "Springfield", "St. Louis", "St. Paul", "St. Petersburg", "Stamford", "Sterling Heights", "Stockton", "Sunnyvale", "Syracuse", "Tacoma", "Tallahassee", "Tampa", "Temecula", "Tempe", "Thornton", "Thousand Oaks", "Toledo", "Topeka", "Torrance", "Trenton", "Tucson", "Tulsa", "Tuscaloosa", "Tyler", "Utica", "Vallejo", "Vancouver", "Vero Beach", "Victorville", "Virginia Beach", "Visalia", "Waco", "Warren", "Washington", "Waterbury", "Waterloo", "West Covina", "West Valley City", "Westminster", "Wichita", "Wilmington", "Winston", "Winter Haven", "Worcester", "Yakima", "Yonkers", "York", "Youngstown"];

        function populateStates() {
            const selectedCountry = $("#country").val();
            if (selectedCountry === "United States") {
                $("#state").html("<option value='' disabled selected>Select a state</option>");
                usStates.forEach((state) => {
                    $("#state").append(`<option value="${state}">${state}</option>`);
                });
            } else {

            }
        }

        function populateCities() {
            const selectedState = $("#state").val();
            if (selectedState) {
                $("#city").prop("disabled", false);
                $("#city").html("<option value='' disabled selected>Select a city</option>");
                usCities.forEach((city) => {
                    $("#city").append(`<option value="${city}">${city}</option>`);
                });
            } else {
                $("#city").prop("disabled", true);
                $("#city").html("<option value='' disabled selected>Select a city</option>");
            }
        }

        $("#country").change(populateStates);
        $("#state").change(populateCities);

        loadGoogleMapsScript();
        populateStates();
        populateCities();
    });

    $('#add-card-form').on('submit', function(e) {
        e.preventDefault();
        $('#card-save-btn').prop('disabled', true);
        $.ajax({
            headers: {
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            url: "{{ route('admin.addStripeAccountProvider') }}",
            type: 'post',
            data: $(this).serialize(),
            success: function(res) {
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