<?php
// Edit Customer Modal
?>
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-labelledby="editCustomerModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCustomerModalLabel">Edit Customer Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCustomerForm">
                <div class="modal-body">
                    <input type="hidden" name="customer_id" value="<?= $customer['id'] ?>">
                    <div class="mb-3">
                        <label for="name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= safeEscape($customer['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= safeEscape($customer['email']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= safeEscape($customer['phone']) ?>">
                    </div>
                    <div class="mb-3">
                        <label for="lifecycle_stage" class="form-label">Lifecycle Stage</label>
                        <select class="form-select" id="lifecycle_stage" name="lifecycle_stage">
                            <?php foreach ($lifecycleStages as $stage): ?>
                            <option value="<?= $stage['id'] ?>" <?= $stage['id'] == $customer['lifecycle_stage_id'] ? 'selected' : '' ?>>
                                <?= safeEscape($stage['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="lead_source" class="form-label">Lead Source</label>
                        <select class="form-select" id="lead_source" name="lead_source">
                            <option value="">Not specified</option>
                            <?php foreach ($leadSources as $source): ?>
                            <option value="<?= $source['id'] ?>" <?= $source['id'] == $customer['lead_source_id'] ? 'selected' : '' ?>>
                                <?= safeEscape($source['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
