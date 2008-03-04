.PHONY: all doc

all: doc

doc:
	cd doc/system && doxygen doxygen.conf

