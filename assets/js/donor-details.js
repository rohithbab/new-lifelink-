$(document).ready(function() {
    // Modal handling
    const modal = document.getElementById('editRequestModal');
    const btn = document.getElementById('editRequestBtn');
    const span = document.getElementsByClassName('close')[0];

    btn.onclick = function() {
        modal.style.display = "block";
    }

    span.onclick = function() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Form submission
    $('#editRequestForm').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '../../backend/php/submit_edit_request.php',
            method: 'POST',
            data: {
                fieldsToEdit: $('#fieldsToEdit').val(),
                reason: $('#reason').val()
            },
            success: function(response) {
                alert('Your edit request has been submitted successfully!');
                modal.style.display = "none";
                $('#editRequestForm')[0].reset();
            },
            error: function(xhr, status, error) {
                alert('Error submitting edit request. Please try again later.');
                console.error(error);
            }
        });
    });

    // Document preview handling
    $('.view-btn').click(function(e) {
        const fileUrl = $(this).attr('href');
        const fileExt = fileUrl.split('.').pop().toLowerCase();
        
        // If it's not an image, let the browser handle it
        if (!['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
            return true;
        }
        
        e.preventDefault();
        
        // Create and show image preview modal
        const previewModal = $(`
            <div class="modal" style="display:block;">
                <div class="modal-content" style="max-width:90%;text-align:center;">
                    <span class="close">&times;</span>
                    <img src="${fileUrl}" style="max-width:100%;max-height:80vh;" />
                </div>
            </div>
        `).appendTo('body');

        // Close preview modal
        previewModal.find('.close').click(function() {
            previewModal.remove();
        });

        previewModal.click(function(e) {
            if (e.target === this) {
                previewModal.remove();
            }
        });
    });
});
