/**
 * Validation functions cho module quản lý cộng tác viên
 * Các hàm validate input cho collaborator (họ tên, số điện thoại, địa chỉ)
 */

// ==================== REGEX PATTERNS ====================
const VALIDATION_PATTERNS = {
    // Regex cho họ tên (chỉ chữ và khoảng trắng)
    NAME: /^[a-zA-ZàáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s]*$/,
    // Regex cho số điện thoại (chỉ số)
    PHONE: /^\d+$/,
    // Regex cho địa chỉ (chữ, số, khoảng trắng và các ký tự .,-/)
    ADDRESS: /^[a-zA-Z0-9àáâãèéêìíòóôõùúýăđĩũơÀÁÂÃÈÉÊÌÍÒÓÔÕÙÚÝĂĐĨŨƠƯĂẠẢẤẦẨẪẬẮẰẲẴẶẸẺẼỀỀỂẾưăạảấầẩẫậắằẳẵặẹẻẽềềểếỄỆỈỊỌỎỐỒỔỖỘỚỜỞỠỢỤỦỨỪễệỉịọỏốồổỗộớờởỡợụủứừỬỮỰỲỴÝỶỸửữựỳỵỷỹ\s\.,\/-]*$/
};

// ==================== VALIDATION CONSTANTS ====================
const VALIDATION_LIMITS = {
    NAME_MAX_LENGTH: 50,
    PHONE_MIN_LENGTH: 9,
    PHONE_MAX_LENGTH: 10,
    ADDRESS_MAX_LENGTH: 80
};

/**
 * Hàm validate họ tên (dùng chung cho cả form và search)
 * @param {string} name - Tên cần validate
 * @param {boolean} isRequired - Có bắt buộc hay không
 * @returns {object} - {isValid: boolean, message: string}
 */
function validateName(name, isRequired = false) {
    const trimmedName = name.trim();
    
    if (isRequired && trimmedName.length === 0) {
        return { isValid: false, message: "Trường này là bắt buộc." };
    }
    
    if (trimmedName.length > VALIDATION_LIMITS.NAME_MAX_LENGTH) {
        return { isValid: false, message: `Họ tên không được vượt quá ${VALIDATION_LIMITS.NAME_MAX_LENGTH} ký tự.` };
    }
    
    if (trimmedName.length > 0 && !VALIDATION_PATTERNS.NAME.test(trimmedName)) {
        return { isValid: false, message: "Họ tên chỉ được chứa chữ." };
    }
    
    return { isValid: true, message: "" };
}

/**
 * Hàm validate số điện thoại (dùng chung cho cả form và search)
 * @param {string} phone - Số điện thoại cần validate
 * @param {boolean} isRequired - Có bắt buộc hay không
 * @returns {object} - {isValid: boolean, message: string}
 */
function validatePhone(phone, isRequired = false) {
    const phoneRaw = phone;
    const phoneTrimmed = phone.trim();
    
    if (isRequired && phoneTrimmed.length === 0) {
        return { isValid: false, message: "Trường này là bắt buộc." };
    }
    
    if (phoneTrimmed.length === 0) {
        return { isValid: true, message: "" }; // Không bắt buộc và rỗng thì hợp lệ
    }
    
    if (/\s/.test(phoneRaw)) {
        return { isValid: false, message: "Số điện thoại không được chứa dấu cách." };
    }
    
    if (!VALIDATION_PATTERNS.PHONE.test(phoneTrimmed)) {
        return { isValid: false, message: "Số điện thoại chỉ được chứa số." };
    }
    
    if (phoneTrimmed.length < VALIDATION_LIMITS.PHONE_MIN_LENGTH || phoneTrimmed.length > VALIDATION_LIMITS.PHONE_MAX_LENGTH) {
        return { isValid: false, message: `Số điện thoại phải có từ ${VALIDATION_LIMITS.PHONE_MIN_LENGTH} đến ${VALIDATION_LIMITS.PHONE_MAX_LENGTH} chữ số.` };
    }
    
    return { isValid: true, message: "" };
}

/**
 * Hàm validate địa chỉ
 * @param {string} address - Địa chỉ cần validate
 * @param {boolean} isRequired - Có bắt buộc hay không
 * @returns {object} - {isValid: boolean, message: string}
 */
function validateAddress(address, isRequired = false) {
    const trimmedAddress = address.trim();
    
    if (isRequired && trimmedAddress.length === 0) {
        return { isValid: false, message: "Trường này là bắt buộc." };
    }
    
    if (trimmedAddress.length > VALIDATION_LIMITS.ADDRESS_MAX_LENGTH) {
        return { isValid: false, message: `Địa chỉ không được vượt quá ${VALIDATION_LIMITS.ADDRESS_MAX_LENGTH} ký tự.` };
    }
    
    if (trimmedAddress.length > 0 && !VALIDATION_PATTERNS.ADDRESS.test(trimmedAddress)) {
        return { isValid: false, message: "Địa chỉ chỉ chứa chữ, số và các ký tự .,-/" };
    }
    
    return { isValid: true, message: "" };
}

