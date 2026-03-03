<?php
/**
 * OTP Service Class
 * Handles OTP generation, validation, and expiration logic
 */

class OTPService {
    private $conn;
    private $otpExpiration = 600; // 10 minutes in seconds
    private $maxResendAttempts = 3;
    private $resendCooldown = 1800; // 30 minutes in seconds
    
    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }
    
    /**
     * Generate a 6-digit OTP
     */
    public function generateOTP() {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create and store OTP for an email
     * @return array ['success' => bool, 'otp' => string, 'message' => string]
     */
    public function createOTP($email) {
        try {
            // Check if there's a recent OTP request (rate limiting)
            $rateLimitCheck = $this->checkRateLimit($email);
            if (!$rateLimitCheck['allowed']) {
                return [
                    'success' => false,
                    'message' => $rateLimitCheck['message']
                ];
            }
            
            // Generate new OTP
            $otp = $this->generateOTP();
            $expiresAt = date('Y-m-d H:i:s', time() + $this->otpExpiration);
            
            // Delete any existing OTP for this email
            $this->deleteOTP($email);
            
            // Insert new OTP
            $stmt = $this->conn->prepare(
                "INSERT INTO otp_verifications (email, otp, expires_at, resend_count, last_resend_at) 
                 VALUES (?, ?, ?, 0, NOW())"
            );
            $stmt->bind_param("sss", $email, $otp, $expiresAt);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'otp' => $otp,
                    'message' => 'OTP generated successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to generate OTP'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Validate OTP for an email
     * @return array ['success' => bool, 'message' => string]
     */
    public function validateOTP($email, $inputOTP) {
        try {
            // Get OTP record
            $stmt = $this->conn->prepare(
                "SELECT id, otp, expires_at, is_verified, attempts 
                 FROM otp_verifications 
                 WHERE email = ? AND is_verified = FALSE 
                 ORDER BY created_at DESC 
                 LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return [
                    'success' => false,
                    'message' => 'No OTP found for this email. Please request a new one.'
                ];
            }
            
            $otpRecord = $result->fetch_assoc();
            
            // Check if OTP is expired
            if (strtotime($otpRecord['expires_at']) < time()) {
                return [
                    'success' => false,
                    'message' => 'OTP has expired. Please request a new one.'
                ];
            }
            
            // Increment attempts
            $this->incrementAttempts($otpRecord['id']);
            
            // Validate OTP
            if ($otpRecord['otp'] === $inputOTP) {
                // Mark as verified
                $this->markAsVerified($otpRecord['id']);
                
                return [
                    'success' => true,
                    'message' => 'OTP verified successfully'
                ];
            } else {
                $remainingAttempts = 5 - ($otpRecord['attempts'] + 1);
                return [
                    'success' => false,
                    'message' => 'Invalid OTP. ' . ($remainingAttempts > 0 ? "$remainingAttempts attempts remaining." : 'Please request a new OTP.')
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Resend OTP to email
     * @return array ['success' => bool, 'otp' => string, 'message' => string]
     */
    public function resendOTP($email) {
        try {
            // Check resend rate limit
            $stmt = $this->conn->prepare(
                "SELECT resend_count, last_resend_at 
                 FROM otp_verifications 
                 WHERE email = ? 
                 ORDER BY created_at DESC 
                 LIMIT 1"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $record = $result->fetch_assoc();
                $timeSinceLastResend = time() - strtotime($record['last_resend_at']);
                
                // Check if within 30-minute window
                if ($timeSinceLastResend < $this->resendCooldown) {
                    // Check resend count
                    if ($record['resend_count'] >= $this->maxResendAttempts) {
                        $remainingTime = ceil(($this->resendCooldown - $timeSinceLastResend) / 60);
                        return [
                            'success' => false,
                            'message' => "Maximum resend attempts reached. Please try again in $remainingTime minutes."
                        ];
                    }
                }
            }
            
            // Generate new OTP
            $otp = $this->generateOTP();
            $expiresAt = date('Y-m-d H:i:s', time() + $this->otpExpiration);
            
            // Delete old OTP
            $this->deleteOTP($email);
            
            // Insert new OTP with incremented resend count
            $resendCount = isset($record) ? $record['resend_count'] + 1 : 1;
            $stmt = $this->conn->prepare(
                "INSERT INTO otp_verifications (email, otp, expires_at, resend_count, last_resend_at) 
                 VALUES (?, ?, ?, ?, NOW())"
            );
            $stmt->bind_param("sssi", $email, $otp, $expiresAt, $resendCount);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'otp' => $otp,
                    'message' => 'OTP resent successfully'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to resend OTP'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Check rate limit for OTP requests
     */
    private function checkRateLimit($email) {
        $stmt = $this->conn->prepare(
            "SELECT resend_count, last_resend_at 
             FROM otp_verifications 
             WHERE email = ? 
             ORDER BY created_at DESC 
             LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $record = $result->fetch_assoc();
            $timeSinceLastResend = time() - strtotime($record['last_resend_at']);
            
            if ($timeSinceLastResend < $this->resendCooldown && $record['resend_count'] >= $this->maxResendAttempts) {
                $remainingTime = ceil(($this->resendCooldown - $timeSinceLastResend) / 60);
                return [
                    'allowed' => false,
                    'message' => "Too many OTP requests. Please try again in $remainingTime minutes."
                ];
            }
        }
        
        return ['allowed' => true];
    }
    
    /**
     * Increment validation attempts
     */
    private function incrementAttempts($otpId) {
        $stmt = $this->conn->prepare("UPDATE otp_verifications SET attempts = attempts + 1 WHERE id = ?");
        $stmt->bind_param("i", $otpId);
        $stmt->execute();
    }
    
    /**
     * Mark OTP as verified
     */
    private function markAsVerified($otpId) {
        $stmt = $this->conn->prepare("UPDATE otp_verifications SET is_verified = TRUE WHERE id = ?");
        $stmt->bind_param("i", $otpId);
        $stmt->execute();
    }
    
    /**
     * Delete OTP for an email
     */
    public function deleteOTP($email) {
        $stmt = $this->conn->prepare("DELETE FROM otp_verifications WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
    }
    
    /**
     * Clean up expired OTP records
     */
    public function cleanupExpiredOTPs() {
        $stmt = $this->conn->prepare("DELETE FROM otp_verifications WHERE expires_at < NOW()");
        return $stmt->execute();
    }
    
    /**
     * Check if email has verified OTP
     */
    public function isOTPVerified($email) {
        $stmt = $this->conn->prepare(
            "SELECT id FROM otp_verifications 
             WHERE email = ? AND is_verified = TRUE 
             ORDER BY created_at DESC 
             LIMIT 1"
        );
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }
}
?>
