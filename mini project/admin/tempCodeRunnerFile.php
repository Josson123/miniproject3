<?php if(!$row['otp']): ?>
            <form method="POST" class="d-inline">
                <input type="hidden" name="booking_no" value="<?php echo $row['booking_no']; ?>">
                <button type="submit" name="generate_otp" class="btn btn-primary">Get OTP</button>
            </form>
        <?php else: ?>
            <div>OTP: <?php echo $row['otp']; ?></div>
            <form method="POST" class="d-inline pickup-form">
                <input type="hidden" name="booking_no" value="<?php echo $row['booking_no']; ?>">
                <input type="hidden" name="user_name" value="<?php echo $row['user_name']; ?>">
                <input type="hidden" name="dropoff_date" value="<?php echo $row['dropoff_date']; ?>">
                <button type="submit" name="confirm_pickup" class="btn btn-success">Confirm Pickup</button>
            </form>
        <?php endif; ?>
    </td>
</tr>