app_name=user_saml

project_dir=$(CURDIR)/../$(app_name)
build_dir=$(CURDIR)/build/artifacts
appstore_dir=$(build_dir)/appstore
source_dir=$(build_dir)/source
sign_dir=$(build_dir)/sign
package_name=$(app_name)
cert_dir=$(HOME)/.nextcloud/certificates
version+=4.0.0

all: appstore

release: appstore create-tag

create-tag:
	git tag -s -a v$(version) -m "Tagging the $(version) release."
	git push origin v$(version)

clean:
	rm -rf $(build_dir)
	rm -rf node_modules

appstore: clean
	composer install --no-dev -d ./3rdparty/
	mkdir -p $(sign_dir)
	rsync -a \
	--exclude=.drone.yml \
	--exclude=.git \
	--exclude=.gitattributes \
	--exclude=.github \
	--exclude=.gitignore \
	--exclude=.scrutinizer.yml \
	--exclude=.travis.yml \
	--exclude=.tx \
	--exclude=/build \
	--exclude=/CONTRIBUTING.md \
	--exclude=/docs \
	--exclude=/issue_template.md \
	--exclude=/l10n/l10n.pl \
	--exclude=/Makefile \
	--exclude=/README.md \
	--exclude=/screenshots \
	--exclude=/tests \
	--exclude=/translationfiles \
	$(project_dir)/ $(sign_dir)/$(app_name)
	tar -czf $(build_dir)/$(app_name).tar.gz \
		-C $(sign_dir) $(app_name)
	@if [ -f $(cert_dir)/$(app_name).key ]; then \
		echo "Signing package…"; \
		openssl dgst -sha512 -sign $(cert_dir)/$(app_name).key $(build_dir)/$(app_name).tar.gz | openssl base64; \
	fi
