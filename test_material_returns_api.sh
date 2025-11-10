#!/bin/bash
# Material Returns API Test Suite
# Comprehensive testing of all 7 endpoints

# ============================================================================
# CONFIGURATION
# ============================================================================

BASE_URL="http://gems.metadatasystem.my/gems2/api"
API_ENDPOINT="$BASE_URL/m_inventory.php"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test data from database
TEST_USER_ID=1
TEST_WO_TASK_PARTS_ID=1
TEST_PART_ID=77
TEST_QUANTITY=2

# JWT Tokens (MUST BE PROVIDED)
JWT_TECHNICIAN=""
JWT_STOREKEEPER=""

# Output file
REPORT_FILE="material_returns_test_report_$(date +%Y%m%d_%H%M%S).md"

# ============================================================================
# HELPER FUNCTIONS
# ============================================================================

print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_test() {
    echo -e "${YELLOW}TEST: $1${NC}"
}

print_success() {
    echo -e "${GREEN}✓ PASS: $1${NC}"
}

print_fail() {
    echo -e "${RED}✗ FAIL: $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ INFO: $1${NC}"
}

# Check if JWT tokens are set
check_tokens() {
    if [ -z "$JWT_TECHNICIAN" ] || [ -z "$JWT_STOREKEEPER" ]; then
        echo -e "${RED}ERROR: JWT tokens not set!${NC}"
        echo ""
        echo "Please set your JWT tokens:"
        echo "  export JWT_TECHNICIAN='your_technician_token'"
        echo "  export JWT_STOREKEEPER='your_storekeeper_token'"
        echo ""
        echo "Or get tokens via login:"
        echo "  curl -X POST $BASE_URL/login.php \\"
        echo "    -H 'Content-Type: application/json' \\"
        echo "    -d '{\"username\":\"your_username\",\"password\":\"your_password\"}'"
        exit 1
    fi
}

# Make API call and return response
api_call() {
    local method=$1
    local endpoint=$2
    local token=$3
    local data=$4
    
    if [ "$method" = "GET" ]; then
        curl -s -X GET "$API_ENDPOINT$endpoint" \
            -H "Authorization: Bearer $token" \
            -H "Content-Type: application/json"
    elif [ "$method" = "POST" ]; then
        curl -s -X POST "$API_ENDPOINT$endpoint" \
            -H "Authorization: Bearer $token" \
            -H "Content-Type: application/json" \
            -d "$data"
    elif [ "$method" = "PUT" ]; then
        curl -s -X PUT "$API_ENDPOINT$endpoint" \
            -H "Authorization: Bearer $token" \
            -H "Content-Type: application/json" \
            -d "$data"
    fi
}

# ============================================================================
# TEST FUNCTIONS
# ============================================================================

test_1_return_eligible_items() {
    print_test "1. GET /return_eligible_items/{userId}"
    
    response=$(api_call "GET" "/return_eligible_items/$TEST_USER_ID" "$JWT_TECHNICIAN")
    
    echo "Response: $response" | head -c 200
    echo ""
    
    if echo "$response" | grep -q '"success":true'; then
        print_success "Can retrieve eligible items"
        ELIGIBLE_ITEMS=$(echo "$response" | grep -o '"woTaskPartsId":"[^"]*"' | head -1 | cut -d'"' -f4)
        if [ ! -z "$ELIGIBLE_ITEMS" ]; then
            print_info "Found eligible item: $ELIGIBLE_ITEMS"
            TEST_WO_TASK_PARTS_ID=$ELIGIBLE_ITEMS
        fi
        return 0
    else
        print_fail "Failed to retrieve eligible items"
        return 1
    fi
}

test_2_submit_return_request() {
    print_test "2. POST /request_return"
    
    data="{
        \"woTaskPartsId\": \"$TEST_WO_TASK_PARTS_ID\",
        \"quantityReturned\": $TEST_QUANTITY,
        \"returnReason\": \"unused_excess\",
        \"returnRemarks\": \"Automated test return\",
        \"returnDeadlineDate\": \"$(date -v+7d '+%Y-%m-%d 17:00:00' 2>/dev/null || date -d '+7 days' '+%Y-%m-%d 17:00:00')\"
    }"
    
    response=$(api_call "POST" "/request_return" "$JWT_TECHNICIAN" "$data")
    
    echo "Response: $response"
    echo ""
    
    if echo "$response" | grep -q '"success":true'; then
        print_success "Return request submitted successfully"
        RETURN_ID=$(echo "$response" | grep -o '"result":[0-9]*' | cut -d':' -f2)
        print_info "Return ID: $RETURN_ID"
        return 0
    else
        print_fail "Failed to submit return request"
        return 1
    fi
}

test_3_storekeeper_pending_returns() {
    print_test "3. GET /storekeeper_pending_returns"
    
    response=$(api_call "GET" "/storekeeper_pending_returns" "$JWT_STOREKEEPER")
    
    echo "Response: $response" | head -c 200
    echo ""
    
    if echo "$response" | grep -q '"success":true'; then
        print_success "Can retrieve pending returns"
        # Extract return ID if exists
        PENDING_RETURN=$(echo "$response" | grep -o '"returnId":"[^"]*"' | head -1 | cut -d'"' -f4)
        if [ ! -z "$PENDING_RETURN" ]; then
            print_info "Found pending return: $PENDING_RETURN"
            RETURN_ID=$PENDING_RETURN
        fi
        return 0
    else
        print_fail "Failed to retrieve pending returns"
        return 1
    fi
}

test_4_return_detail() {
    print_test "4. GET /return_detail/{returnId}"
    
    if [ -z "$RETURN_ID" ]; then
        print_info "No return ID available, skipping..."
        return 0
    fi
    
    response=$(api_call "GET" "/return_detail/$RETURN_ID" "$JWT_STOREKEEPER")
    
    echo "Response: $response" | head -c 200
    echo ""
    
    if echo "$response" | grep -q '"success":true'; then
        print_success "Can retrieve return details"
        return 0
    else
        print_fail "Failed to retrieve return details"
        return 1
    fi
}

test_5_confirm_return() {
    print_test "5. PUT /confirm_return/{returnId}"
    
    if [ -z "$RETURN_ID" ]; then
        print_info "No return ID available, skipping..."
        return 0
    fi
    
    response=$(api_call "PUT" "/confirm_return/$RETURN_ID" "$JWT_STOREKEEPER" "{}")
    
    echo "Response: $response"
    echo ""
    
    if echo "$response" | grep -q '"success":true'; then
        print_success "Return confirmed successfully"
        print_info "Inventory should be updated now"
        return 0
    else
        print_fail "Failed to confirm return"
        return 1
    fi
}

test_6_return_history() {
    print_test "6. GET /return_history?userId={userId}&status=all"
    
    response=$(api_call "GET" "/return_history?userId=$TEST_USER_ID&status=all" "$JWT_TECHNICIAN")
    
    echo "Response: $response" | head -c 200
    echo ""
    
    if echo "$response" | grep -q '"success":true'; then
        print_success "Can retrieve return history"
        return 0
    else
        print_fail "Failed to retrieve return history"
        return 1
    fi
}

test_7_return_statistics() {
    print_test "7. GET /return_statistics?userId={userId}"
    
    response=$(api_call "GET" "/return_statistics?userId=$TEST_USER_ID" "$JWT_TECHNICIAN")
    
    echo "Response: $response"
    echo ""
    
    if echo "$response" | grep -q '"success":true'; then
        print_success "Can retrieve statistics"
        return 0
    else
        print_fail "Failed to retrieve statistics"
        return 1
    fi
}

# Error handling tests
test_error_invalid_quantity() {
    print_test "ERROR TEST: Invalid quantity (999)"
    
    data="{
        \"woTaskPartsId\": \"$TEST_WO_TASK_PARTS_ID\",
        \"quantityReturned\": 999,
        \"returnReason\": \"unused_excess\"
    }"
    
    response=$(api_call "POST" "/request_return" "$JWT_TECHNICIAN" "$data")
    
    if echo "$response" | grep -q "Cannot return more than collected"; then
        print_success "Correctly rejected invalid quantity"
        return 0
    else
        print_fail "Did not reject invalid quantity"
        return 1
    fi
}

test_error_invalid_reason() {
    print_test "ERROR TEST: Invalid return reason"
    
    data="{
        \"woTaskPartsId\": \"$TEST_WO_TASK_PARTS_ID\",
        \"quantityReturned\": 1,
        \"returnReason\": \"invalid_reason_xyz\"
    }"
    
    response=$(api_call "POST" "/request_return" "$JWT_TECHNICIAN" "$data")
    
    if echo "$response" | grep -q "Invalid return reason"; then
        print_success "Correctly rejected invalid reason"
        return 0
    else
        print_fail "Did not reject invalid reason"
        return 1
    fi
}

# ============================================================================
# MAIN TEST EXECUTION
# ============================================================================

main() {
    print_header "Material Returns API Test Suite"
    echo "Base URL: $API_ENDPOINT"
    echo "Test User ID: $TEST_USER_ID"
    echo "Test WO Task Parts ID: $TEST_WO_TASK_PARTS_ID"
    echo ""
    
    # Check tokens
    check_tokens
    
    # Initialize counters
    TOTAL_TESTS=0
    PASSED_TESTS=0
    
    # Run tests
    echo ""
    print_header "HAPPY PATH TESTS"
    
    tests=(
        "test_1_return_eligible_items"
        "test_2_submit_return_request"
        "test_3_storekeeper_pending_returns"
        "test_4_return_detail"
        "test_5_confirm_return"
        "test_6_return_history"
        "test_7_return_statistics"
    )
    
    for test in "${tests[@]}"; do
        echo ""
        $test
        TOTAL_TESTS=$((TOTAL_TESTS + 1))
        if [ $? -eq 0 ]; then
            PASSED_TESTS=$((PASSED_TESTS + 1))
        fi
        sleep 1
    done
    
    echo ""
    print_header "ERROR HANDLING TESTS"
    
    error_tests=(
        "test_error_invalid_quantity"
        "test_error_invalid_reason"
    )
    
    for test in "${error_tests[@]}"; do
        echo ""
        $test
        TOTAL_TESTS=$((TOTAL_TESTS + 1))
        if [ $? -eq 0 ]; then
            PASSED_TESTS=$((PASSED_TESTS + 1))
        fi
        sleep 1
    done
    
    # Summary
    echo ""
    print_header "TEST SUMMARY"
    echo "Total Tests: $TOTAL_TESTS"
    echo "Passed: $PASSED_TESTS"
    echo "Failed: $((TOTAL_TESTS - PASSED_TESTS))"
    
    if [ $PASSED_TESTS -eq $TOTAL_TESTS ]; then
        print_success "All tests passed! ✓"
        exit 0
    else
        print_fail "Some tests failed"
        exit 1
    fi
}

# ============================================================================
# USAGE
# ============================================================================

if [ "$1" = "--help" ] || [ "$1" = "-h" ]; then
    echo "Material Returns API Test Suite"
    echo ""
    echo "Usage:"
    echo "  1. Set JWT tokens:"
    echo "     export JWT_TECHNICIAN='your_token'"
    echo "     export JWT_STOREKEEPER='your_token'"
    echo ""
    echo "  2. Run tests:"
    echo "     ./test_material_returns_api.sh"
    echo ""
    echo "  Or set tokens inline:"
    echo "     JWT_TECHNICIAN='token1' JWT_STOREKEEPER='token2' ./test_material_returns_api.sh"
    echo ""
    exit 0
fi

# Run main
main
