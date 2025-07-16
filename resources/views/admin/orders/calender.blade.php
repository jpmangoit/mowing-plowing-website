@extends('layouts.admin')

@section('title', 'Admins')

@section('title', 'Job Details')

@push('vendor-styles')
@endpush

@push('page-styles')
    <style>
    .calendar-hint-box {
          padding: 10px;
          display: flex;
      }

      .calendar-hint-box .order-info {
          display: flex;
          flex-direction: row;
          padding: 10px 10px 10px 0;
          border: 1px dashed;
      }

      .calendar-hint-box .order-info .order-info-label {display:flex;flex-direction:column;padding: 10px 10px 0 10px;}

      .calendar-hint-box .order-info .order-info-colors { display: flex; flex-direction:column;}
      .page-body-wrapper{
        background-color: white !important;
      }
      /* .ui-dialog-titlebar{
        background-color: green !important;
      } */
      .ui-dialog-buttonset button {
          border: 1px black solid;
          background-color: limegreen; /* Initial background color */
          padding: 5px 10px;
          cursor: pointer;
      }

      /* CSS for the hover effect */
      .ui-dialog-buttonset button:hover {
          border: 1px black solid;
          background-color: white; /* Change background color on hover */
          color: green; /* Change text color on hover */
      }

      .info-icon {
        position: relative;
        display: inline-block;
        cursor: pointer;
    }

    .info-icon::after {
        content: attr(data-tooltip);
        position: absolute;
        background-color: #333;
        color: #fff;
        padding: 5px;
        border-radius: 5px;
        font-size: 12px;
        width: 120px;
        text-align: center;
        visibility: hidden;
        opacity: 0;
        transition: opacity 0.3s;
        top: calc(100% + 5px);
        left: 50%;
        transform: translateX(-50%);
        z-index: 6666;
        
    }

    .info-icon:hover::after {
        visibility: visible;
        opacity: 1;
    }
    </style>
@endpush

@section('body')

<div class="calendar-hint-box">
  <div class="order-info">
    <div class="order-info-label"> One time Order Colors :</div>
    <div class="order-info-colors" style="">
      <div>
        <div style="display: inline-block; width: 15px; height: 15px; background-color: #FA8072; border: 1px solid black;"></div>
        <span style="vertical-align: top; margin-left: 5px;">Failed</span>
      </div>
      <div>
        <div style="display: inline-block; width: 15px; height: 15px; background-color: limegreen; border: 1px solid black;"></div>
        <span style="vertical-align: top; margin-left: 5px;">Completed</span>
      </div>
      <div>
        <div style="display: inline-block; width: 15px; height: 15px; background-color: orange; border: 1px solid black;"></div>
        <span style="vertical-align: top; margin-left: 5px;">Active</span>
      </div>
    </div>
  </div>

  <div class="order-info">
    <div class="order-info-label"> Recurring Information Colors :</div>
    <div class="order-info-colors" style="">
      <div>
        <div style="display: inline-block; width: 15px; height: 15px; background-color: red; border: 1px solid black;"></div>
        <span style="vertical-align: top; margin-left: 5px;">Failed</span>
      </div>
      <div>
        <div style="display: inline-block; width: 15px; height: 15px; background-color: green; border: 1px solid black;"></div>
        <span style="vertical-align: top; margin-left: 5px;">Completed</span>
      </div>
      <div>
        <div style="display: inline-block; width: 15px; height: 15px; background-color: blue; border: 1px solid black;"></div>
        <span style="vertical-align: top; margin-left: 5px;">Active</span>
      </div>
    </div>
  </div>
</div>

<div id='calendar'></div>
<div id="dialog" title="Reschedule Event" style="display:none">
  <div class="pop">
    <button id="rescheduleThis">Reschedule This Event</button>
    <button id="rescheduleAll">Reschedule All Events</button>
  </div>
</div>
@endsection
@push('vendor-scripts')
@endpush

@push('page-scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.1/jquery.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.17.1/moment.min.js'></script>
<script src='https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.2.0/fullcalendar.min.js'></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script>
$(document).ready(function() {
  document.addEventListener('DOMContentLoaded', function() {
    var tooltips = document.querySelectorAll('.info-icon');
    tooltips.forEach(function(tooltip) {
      new Tooltip(tooltip, {
        title: tooltip.getAttribute('data-tooltip'),
        html: true,
        placement: 'top' // Adjust the placement as needed
      });
    });
  });
  function showTooltip() {
        var tooltip = document.getElementById("tooltip");
        tooltip.style.visibility = "visible";
    }

    function hideTooltip() {
        var tooltip = document.getElementById("tooltip");
        tooltip.style.visibility = "hidden";
    }

  var events = {!! json_encode($events) !!};
  var recurringEvents = {!! json_encode($recurring_events) !!};
  // console.log(recurringEvents, 'recurringEvents');
  var allEvents = events.concat(recurringEvents);
  // var allEvents = recurringEvents;

  recurringEvents.forEach(item => {
    // Get the start date of the event
    let startDate = moment(item.start, 'MM/DD/YYYY');
    let endDate = item.end_date;
    // Calculate the end date (2 months from the start date)
    if(item.end_date == null){  
     endDate = moment(startDate).add(4, 'months');
    }
    
    // Calculate the next occurrence date based on the 'on_every' value
    let nextOccurrence = moment(startDate).add(item.on_every, 'days');
    // if(item.status=='Active'){
      if(item.on_every !==0){
        while (nextOccurrence.isBefore(endDate)) {
            // Create a new event object for each occurrence
          let newEvent = {
              name:item.name,
              id: item.id,
              title: item.title,
              title1: item.title1,
              start: nextOccurrence.format('YYYY-MM-DD'), // Format the date as 'YYYY-MM-DD'
              status: item.status,
              user_name: item.user_name,
              order_id: item.order_id,
              order_address: item.order_address,
              on_every: item.on_every
          };
          
          // Add the new event to the allEvents array
          if(item.status == 'Active'){
            allEvents.push(newEvent);
          }
          
          // Move to the next occurrence date
          nextOccurrence.add(item.on_every, 'days');
        }
      }
    // }
  });
 
  $('#calendar').fullCalendar({
    header: {
      left: 'prev,next today',
      center: 'title',
      right: 'month' // Add day and week views
    },
    editable: false, // Disable event dragging and resizing
    eventLimit: true, // allow "more" link when too many events
    events: allEvents,
    eventClick: function (calEvent, jsEvent, view) {
      
      // Open the dialog/modal
      // console.log(calEvent.status);
      if(calEvent.name == 'RecurringEvents' && (calEvent.status == 'Active') && calEvent.on_every !=0){
        console.log("jaspreet");
        $("#dialog").dialog({
          autoOpen: false,
          modal: true,
          title: calEvent.title,
          width: 'auto',
          create: function (event, ui) {
              // Append close icon (X) to the title bar
              var closeButton = $('<button>')
                  .addClass('ui-dialog-titlebar-close')
                  .append($('<p>').addClass('ui-icon ui-icon-closethick'))
                  .on('click', function () {
                      $("#dialog").dialog('close');
                  });
              $(this).closest('.ui-dialog').find('.ui-dialog-titlebar').append(closeButton);
            //   var inputField = '<input type="text" id="newDateInput" placeholder="YYYY-MM-DD" required>';
            // $(this).append(inputField);
              // Add an input field inside the dialog
          },
          buttons: {
            "Reschedule This Event": function() {
              var currentDate = moment().subtract(1, 'days').format('YYYY-MM-DD');
              var newDate = $('#newDateInput').val();
              // var newDate = prompt("Enter the new date for the event (YYYY-MM-DD):");
              console.log(newDate,"SNDJASKJDNFASKJF");
              if (newDate) {
                // console.log("New date:", newDate);
                // Handle the rescheduling logic here using the newDate
                // This is just an example, you can replace it with your logic
                console.log(calEvent,"Reschedule this event clicked");
                var selectedDate = moment(newDate, 'YYYY-MM-DD');

                // Validate if the selected date is after the current date
                if (selectedDate.isValid() && selectedDate.isAfter(currentDate)) {
                  var previousDate = newDate ? newDate : previousDate; 
                  // var newDate = calEvent.start.format().slice(0, -1)// Use the start date if end date is not available
                  var newDate = calEvent.start.format().split('T')[0];
                  var formattedDate = new Date(newDate).toISOString().split('T')[0];
                  var titlePrefix = calEvent.title1.split(' ')[0];
                  // When an event is dropped, make an AJAX call to update the event date
                  var eventData = {
                      id: calEvent.id,
                      previousDate: previousDate,
                      newDate: newDate,
                      title: titlePrefix,
                      recurringJob: 1,
                      oneTime: 0,
                      order_id: calEvent.order_id,
                  };

                  console.log(eventData,"event");

                  $.ajax({
                      headers: {
                          'X-CSRF-TOKEN': "{{ csrf_token() }}"
                      },
                      type: 'POST',
                      url: "{{ route('admin.order.updateOrderDate') }}",
                      data: eventData,
                      success: function(response) {
                          console.log('Event moved successfully.');
                          $("#dialog").dialog("close");
                          window.location.reload();
                      },
                      error: function(xhr, status, error) {
                          console.error('Error moving event:', error);
                          alert(error);
                          // revertFunc(); // Revert event back to its original position if there's an error
                      }
                  });
                }else{
                  swal("Please enter a valid date after the current date.", {
                    icon: "warning",
                  });
                }
              }
            },
            "Reschedule All Matching Events": function() {
              var currentDate = moment().subtract(1, 'days').format('YYYY-MM-DD');
              var newDate = $('#newDateInput').val();
              console.log(newDate,"SNDJASKJDNFASKJF");
              // var newDate = prompt("Enter the new date for the event (YYYY-MM-DD):");
              // Handle reschedule action for all events
              // Implement your logic here, e.g., show a confirmation or trigger a function
              if (newDate) {
                  // console.log("New date:", newDate);
                  // Handle the rescheduling logic here using the newDate
                  // This is just an example, you can replace it with your logic
                var selectedDate = moment(newDate, 'YYYY-MM-DD');
                console.log(calEvent.start.format().split('T')[0],"previous date");
                // Validate if the selected date is after the current date
                if (selectedDate.isValid() && selectedDate.isAfter(currentDate)) {
                  // console.log(calEvent,"Reschedule this event clicked");
                  var previousDate = newDate ? newDate : previousDate; 
                  // var newDate = calEvent.start.format().slice(0, -1)// Use the start date if end date is not available
                  var newDate = calEvent.start.format().split('T')[0];
                  var formattedDate = new Date(newDate).toISOString().split('T')[0];
                  var titlePrefix = calEvent.title1.split(' ')[0];
                  // When an event is dropped, make an AJAX call to update the event date

                  console.log(formattedDate,"formattedDate");
                  var eventData = {
                      id: calEvent.id,
                      previousDate: previousDate,
                      newDate: newDate,
                      title: titlePrefix,
                      recurringJob: 2,
                      oneTime: 0,
                      order_id: calEvent.order_id,
                  };

                  $.ajax({
                      headers: {
                          'X-CSRF-TOKEN': "{{ csrf_token() }}"
                      },
                      type: 'POST',
                      url: "{{ route('admin.order.updateOrderDate') }}",
                      data: eventData,
                      success: function(response) {
                          console.log('Event moved successfully.');
                          $("#dialog").dialog("close");
                          window.location.reload();
                      },
                      error: function(xhr, status, error) {
                          console.error('Error moving event:', error);
                          alert(error);
                          // revertFunc(); // Revert event back to its original position if there's an error
                      }
                  });
                }else{
                  swal("Please enter a valid date after the current date.", {
                    icon: "warning",
                  });
                }
              }
            },
            "Close": function() {
              $(this).dialog("close");
            }
          }
        });
        // Set the content of the dialog/modal
	      var currentDate = new Date();

	      // Format the current date to YYYY-MM-DD format
	      var formattedDate = currentDate.toISOString().slice(0,10);
        var content = "<p>User Name: " + calEvent.user_name + "</p>";
        content += "<p>Order Address: " + calEvent.order_address + "</p>";
        content += "<p>Order Status: " + calEvent.status + "</p>";
	      content += '<input type="date" id="newDateInput" value="' + formattedDate + '">';
        $("#dialog").html(content);
        // $(this).append(inputField);
        // $("#dialog").html(inputField);
        // Open the dialog/modal
        $("#dialog").dialog('open');
      } else if(calEvent.name == 'RecurringEvents' && calEvent.on_every == 0 && calEvent.status == 'Active'){
        $("#dialog").dialog({
          autoOpen: false,
          modal: true,
          title: calEvent.title,
          width: 'auto',
          create: function (event, ui) {
              // Append close icon (X) to the title bar
              var closeButton = $('<button>')
                  .addClass('ui-dialog-titlebar-close')
                  .append($('<p>').addClass('ui-icon ui-icon-closethick'))
                  .on('click', function () {
                      $("#dialog").dialog('close');
                  });
              $(this).closest('.ui-dialog').find('.ui-dialog-titlebar').append(closeButton);

              // Add an input field inside the dialog
          },
          buttons: {
            "Reschedule This Event": function() {
                // var newDate = prompt("Enter the new date for the event (YYYY-MM-DD):");
                var currentDate11 = moment().subtract(1, 'days').format('YYYY-MM-DD');
                var newDate = $('#newDateInput').val();
                if (newDate) {
                  // console.log("New date:", newDate);
                  // Handle the rescheduling logic here using the newDate
                  // This is just an example, you can replace it with your logic
                  var selectedDate = moment(newDate, 'YYYY-MM-DD');
                  if (selectedDate.isValid() && selectedDate.isAfter(currentDate11)) {
                    console.log(calEvent,"Reschedule this event clicked");
                    var previousDate = newDate ? newDate : previousDate; 
                    var newDate = calEvent.start.format().slice(0, -1)// Use the start date if end date is not available
                    var formattedDate = new Date(newDate).toISOString().split('T')[0];
                    var titlePrefix = calEvent.title1.split(' ')[0];
                    // When an event is dropped, make an AJAX call to update the event date
                    var eventData = {
                        id: calEvent.id,
                        previousDate: previousDate,
                        newDate: formattedDate,
                        title: titlePrefix,
                        recurringJob: 1,
                        oneTime: 1,
                    };

                    console.log(eventData,"event");

                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        type: 'POST',
                        url: "{{ route('admin.order.updateOrderDate') }}",
                        data: eventData,
                        success: function(response) {
                            console.log('Event moved successfully.');
                            $("#dialog").dialog("close");
                            window.location.reload();
                        },
                        error: function(xhr, status, error) {
                            console.error('Error moving event:', error);
                            alert(error);
                            // revertFunc(); // Revert event back to its original position if there's an error
                        }
                    });
                  }else{
                    swal("Please enter a valid date after the current date.", {
                      icon: "warning",
                    });
                  }
                }
            },
            "Close": function() {
              $(this).dialog("close");
            }
          }
        });
        	var currentDate = new Date();

	// Format the current date to YYYY-MM-DD format
	      var formattedDate = currentDate.toISOString().slice(0,10);
        var content = "<p>User Name: " + calEvent.user_name + "</p>";
        content += "<p>Order Address: " + calEvent.order_address + "</p>";
        content += "<p>Order Status: " + calEvent.status + "</p>";
	      content += '<input type="date" id="newDateInput" value="' + formattedDate + '">';
        $("#dialog").html(content);
        // $("#dialog").html(content);
        // Open the dialog/modal
        $("#dialog").dialog('open');
      }else if(calEvent.name == 'OrderEvent' && (calEvent.status == 1 || calEvent.status == 2)){
        console.log(calEvent,"calEvent");
        var currentDate11 = moment().subtract(1, 'days').format('YYYY-MM-DD');
        $("#dialog").dialog({
          autoOpen: false,
          modal: true,
          title: calEvent.title,
          width: 'auto',
          create: function (event, ui) {
              // Append close icon (X) to the title bar
              var closeButton = $('<button>')
                  .addClass('ui-dialog-titlebar-close')
                  .append($('<p>').addClass('ui-icon ui-icon-closethick'))
                  .on('click', function () {
                      $("#dialog").dialog('close');
                  });
              $(this).closest('.ui-dialog').find('.ui-dialog-titlebar').append(closeButton);
          },
          buttons: {
            "Reschedule This Event": function() {
                // var newDate = prompt("Enter the new date for the event (YYYY-MM-DD):");
                var newDate = $('#newDateInput').val();
                if (newDate) {
                	var selectedDate = moment(newDate, 'YYYY-MM-DD');
                  if (selectedDate.isValid() && selectedDate.isAfter(currentDate11)) {
                    // console.log("New date:", newDate);
                    // Handle the rescheduling logic here using the newDate
                    // This is just an example, you can replace it with your logic
                    console.log(calEvent,"Reschedule this event clicked");
                    var previousDate = newDate ? newDate : previousDate; 
                    var newDate = calEvent.start.format().slice(0, -1)// Use the start date if end date is not available
                    var formattedDate = new Date(newDate).toISOString().split('T')[0];
                    var titlePrefix = calEvent.title1.split(' ')[0];
                    // When an event is dropped, make an AJAX call to update the event date
                    var eventData = {
                        id: calEvent.id,
                        previousDate: previousDate,
                        newDate: formattedDate,
                        title: titlePrefix,
                        recurringJob: 1,
                    };

                    console.log(eventData,"event");

                    $.ajax({
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        type: 'POST',
                        url: "{{ route('admin.order.updateOrderDate') }}",
                        data: eventData,
                        success: function(response) {
                            console.log('Event moved successfully.');
                            $("#dialog").dialog("close");
                            window.location.reload();
                        },
                        error: function(xhr, status, error) {
                            console.error('Error moving event:', error);
                            alert(error);
                            // revertFunc(); // Revert event back to its original position if there's an error
                        }
                    });
                  }else{
                    swal("Please enter a valid date after the current date.", {
                      icon: "warning",
                    });
                  }
                }
            },
            "Close": function() {
              $(this).dialog("close");
            }
          }
        });
        // Set the content of the dialog/modal
        	var currentDate = new Date();

	if(calEvent.status === 2){	
	    var status = "Active";
	}
	if(calEvent.status === 1){	
	    var status = "Pending";
	}
	// Format the current date to YYYY-MM-DD format
	var formattedDate = currentDate.toISOString().slice(0,10);
        var content = "<p>User Name: " + calEvent.user_name + "</p>";
        content += "<p>Order Address: " + calEvent.order_address + "</p>";
        content += "<p>Order Status: " + status + "</p>";
	content += '<input type="date" id="newDateInput" value="' + formattedDate + '">';
        $("#dialog").html(content);
        // Open the dialog/modal
        $("#dialog").dialog('open');
      }else{
        $("#dialog").dialog({
          autoOpen: false,
          modal: true,
          title: calEvent.title,
          width: 'auto',
          buttons: {
            "Close": function() {
                $(this).dialog("close");
            }
        },
          create: function (event, ui) {
              // Append close icon (X) to the title bar
              var closeButton = $('<button>')
                  .addClass('ui-dialog-titlebar-close')
                  .append($('<p>').addClass('ui-icon ui-icon-closethick'))
                  .on('click', function () {
                      $("#dialog").dialog('close');
                  });
              $(this).closest('.ui-dialog').find('.ui-dialog-titlebar').append(closeButton);
          },
        });
        // Set the content of the dialog/modal
       	var currentDate = new Date();

	// Format the current date to YYYY-MM-DD format
        var content = "<p>User Name: " + calEvent.user_name + "</p>";
        content += "<p>Order Address: " + calEvent.order_address + "</p>";
        content += "<p>Order Status: " + calEvent.status + "</p>";
        $("#dialog").html(content);
        // Open the dialog/modal
        $("#dialog").dialog('open');
      }
    },
    eventDrop: function(event, delta, revertFunc) {
      // Check if the event has an orange background color
      if (event.status === 4) {
        // console.log(event,"event with background color");
        alert('Completed jobs date cannot be changed');
        // Revert the event to its original position
        revertFunc();
      } else {
        // Proceed with the AJAX call to update the event date
        var previousDate = event.start.format(); // Format the start date
        var newDate = event.end ? event.end.format() : previousDate; // Use the start date if end date is not available
        var formattedDate = new Date(newDate).toISOString().split('T')[0];
        var titlePrefix = event.title.split(' ')[0];
        console.log(formattedDate, "formattedDate");
        // When an event is dropped, make an AJAX call to update the event date
        var eventData = {
            id: event.id,
            previousDate: previousDate,
            newDate: formattedDate,
            title: titlePrefix,
        };

        console.log(titlePrefix,"event");

        $.ajax({
          headers: {
                  'X-CSRF-TOKEN': "{{ csrf_token() }}"
              },
          type: 'POST',
          url: "{{ route('admin.order.updateOrderDate') }}",
          data: eventData,
          success: function(response) {
            console.log('Event moved successfully.');
          },
          error: function(xhr, status, error) {
            console.error('Error moving event:', error);
            revertFunc(); // Revert event back to its original position if there's an error
          }
        });
      }
    },
 
    eventRender: function(event, element) {
      event.allDay =true;
      // console.log(event,"inside event");
      element.find('.fc-time').hide();
      // console.log(event,"event rendered");
      if (event.status === 2 || event.status === 1) {
        element.css('background-color', 'orange');
        element.data('editable', false); // Set editable to false for events with orange background
      }
      if (event.status === 3) {
        element.css('background-color', 'limegreen');
        element.data('editable', false); // Set editable to false for events with orange background
      }
      if (event.status === 4) {
        element.css('background-color', '#FA8072');
        element.data('editable', false); // Set editable to false for events with orange background
      }
      if(event.status == "Failed" || event.status == "Cancel"){
        element.css('background-color', 'red');
        element.data('editable', false); // Set editable to false for events with orange background
      }
      if(event.status == "Active"){
        element.css('background-color', 'blue');
        // element.data('editable', false); // Set editable to false for events with orange background
      }
      if(event.status == "Completed"){
        element.css('background-color', 'green');
        // element.data('editable', false); // Set editable to false for events with orange background
      }
    }
  });
});
</script>
@endpush

