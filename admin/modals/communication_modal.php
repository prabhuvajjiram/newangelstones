<?php
// Get customers for dropdown
$stmt = $pdo->query("SELECT id, name FROM customers ORDER BY name");
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- New Communication Modal -->
<div class="modal fade" id="newCommunicationModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Log Communication</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="newCommunicationForm" action="ajax/log_communication.php" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <select class="form-select" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                            <option value="<?= $customer['id'] ?>"><?= htmlspecialchars($customer['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Communication Type</label>
                        <select class="form-select" name="type" required>
                            <option value="email">Email</option>
                            <option value="phone">Phone</option>
                            <option value="meeting">Meeting</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" class="form-control" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content</label>
                        <textarea class="form-control" name="content" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Log Communication</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#newCommunicationForm').submit(function(e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize())
            .done(function(response) {
                location.reload();
            })
            .fail(function(xhr) {
                alert('Error logging communication: ' + xhr.responseText);
            });
    });
});
</script>
