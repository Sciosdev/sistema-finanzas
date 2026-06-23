import Swal from 'sweetalert2';

//Basic
if (document.getElementById("sweetalert-basic"))
    document.getElementById("sweetalert-basic").addEventListener("click", function () {
        Swal.fire({
            title: 'Any fool can use a computer',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            showCloseButton: false
        })
    });

//A title with a text under
if (document.getElementById("sweetalert-title"))
    document.getElementById("sweetalert-title").addEventListener("click", function () {
        Swal.fire({
            title: "The Internet?",
            text: 'That thing is still around?',
            icon: 'question',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            showCloseButton: false
        })
    });

//Success Message
if (document.getElementById("sweetalert-success"))
    document.getElementById("sweetalert-success").addEventListener("click", function () {
        Swal.fire({
            title: 'Good job!',
            text: 'You clicked the button!',
            icon: 'success',
            showCancelButton: true,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            cancelButtonClass: 'btn btn-danger w-xs mt-2',
            showCloseButton: false
        })
    });

//error Message
if (document.getElementById("sweetalert-error"))
    document.getElementById("sweetalert-error").addEventListener("click", function () {
        Swal.fire({
            title: 'Oops...',
            text: 'Something went wrong!',
            icon: 'error',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            footer: '<a href="">Why do I have this issue?</a>',
            showCloseButton: false
        })
    });


//info Message
if (document.getElementById("sweetalert-info"))
    document.getElementById("sweetalert-info").addEventListener("click", function () {
        Swal.fire({
            title: 'Oops...',
            text: 'Something went wrong!',
            icon: 'info',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            footer: '<a href="">Why do I have this issue?</a>',
            showCloseButton: false
        })
    });

//Warning Message
if (document.getElementById("sweetalert-warning"))
    document.getElementById("sweetalert-warning").addEventListener("click", function () {
        Swal.fire({
            title: 'Oops...',
            text: 'Something went wrong!',
            icon: 'warning',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            footer: '<a href="">Why do I have this issue?</a>',
            showCloseButton: false
        })
    });

// long content
if (document.getElementById("sweetalert-longcontent"))
    document.getElementById("sweetalert-longcontent").addEventListener("click", function () {
        Swal.fire({
            imageUrl: 'https://placeholder.pics/svg/300x1500',
            imageHeight: 1500,
            imageAlt: 'A tall image',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            showCloseButton: false
        })
    });


//Parameter
if (document.getElementById("sweetalert-params"))
    document.getElementById("sweetalert-params").addEventListener("click", function () {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'No, cancel!',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            cancelButtonClass: 'btn btn-danger w-xs mt-2',
            showCloseButton: false
        }).then(function (result) {
            if (result.value) {
                Swal.fire({
                    title: 'Deleted!',
                    text: 'Your file has been deleted.',
                    icon: 'success',
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                })
            } else if (
                // Read more about handling dismissals
                result.dismiss === Swal.DismissReason.cancel
            ) {
                Swal.fire({
                    title: 'Cancelled',
                    text: 'Your imaginary file is safe :)',
                    icon: 'error',
                    confirmButtonColor: "#DD6B55",
                    confirmButtonText: "Yes, delete it!",
                })
            }
        });
    });


//Custom Image
if (document.getElementById("sweetalert-image"))
    document.getElementById("sweetalert-image").addEventListener("click", function () {
        Swal.fire({
            title: 'Sweet!',
            text: 'Modal with a custom image.',
            imageUrl: 'assets/images/logo-sm.png',
            imageHeight: 40,
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "Yes, delete it!",
            animation: false,
            showCloseButton: false
        })
    });