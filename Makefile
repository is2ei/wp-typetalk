OS = $(shell uname)

# COLORS
RED    = $(shell printf "\33[31m")
GREEN  = $(shell printf "\33[32m")
WHITE  = $(shell printf "\33[37m")
YELLOW = $(shell printf "\33[33m")
RESET = $(shell printf "\33[0m")

#=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
#
#  HELP
#
#=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

.DEFAULT: help

#=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
#
#  TEST
#
#=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

test_prepare:
	@echo "${YELLOW}Preparing for tests${RESET}\n"
	@bash bin/install-wp-tests.sh wp wp password 127.0.0.1 latest
	@echo "${GREEN}✔ well done!${RESET}\n"
test:
	@echo "${YELLOW}Running all tests${RESET}\n"
	@./phpunit
	@echo "${GREEN}✔ well done!${RESET}\n"