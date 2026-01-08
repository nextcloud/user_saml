<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\User_SAML\Service;

use OCA\User_SAML\Db\SessionData;
use OCA\User_SAML\Db\SessionDataMapper;
use OCA\User_SAML\Model\SessionData as SessionDataModel;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\Authentication\Token\IProvider;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\ISession;
use OneLogin\Saml2\Auth;
use Psr\Log\LoggerInterface;

class SessionService {
	public const ENVIRONMENT_IDENTITY_PROVIDER_ID = 1;

	public function __construct(
		protected readonly ISession $session,
		protected readonly SessionDataMapper $sessionDataMapper,
		protected readonly LoggerInterface $logger,
		protected readonly IProvider $tokenProvider,
		protected readonly IConfig $config,
	) {
	}

	public function isActiveSamlSession(): bool {
		return $this->session->get(SessionDataModel::KEY_IDENTITY_PROVIDER_ID) !== null;
	}

	public function storeIdentityProviderInSession(int $idp): void {
		$this->session->set(SessionDataModel::KEY_IDENTITY_PROVIDER_ID, $idp);
	}

	public function restoreSession(string $oldSessionId): void {
		try {
			$sessionIdHash = $this->hashSessionId($oldSessionId);
			$sessionData = $this->sessionDataMapper->retrieve($sessionIdHash);
			$this->storeSessionDataInSession($sessionData);
			$this->storeSessionDataInDatabase();
			$this->logger->debug('SAML session successfully restored');
			// we do not delete the old session automatically to avoid race conditions
		} catch (DoesNotExistException|MultipleObjectsReturnedException|Exception $e) {
			return;
		}
	}

	public function prepareEnvironmentBasedSession(array $env): void {
		$this->storeIdentityProviderInSession(self::ENVIRONMENT_IDENTITY_PROVIDER_ID);
		$this->session->set('user_saml.samlUserData', $env);
	}

	public function storeAuthDataInSession(Auth $auth): void {
		$this->session->set('user_saml.samlUserData', $auth->getAttributes());
		$this->session->set('user_saml.samlNameId', $auth->getNameId());
		$this->session->set('user_saml.samlNameIdFormat', $auth->getNameIdFormat());
		$this->session->set('user_saml.samlNameIdNameQualifier', $auth->getNameIdNameQualifier());
		$this->session->set('user_saml.samlNameIdSPNameQualifier', $auth->getNameIdSPNameQualifier());
		$this->session->set('user_saml.samlSessionIndex', $auth->getSessionIndex());
		$this->session->set('user_saml.samlSessionExpiration', $auth->getSessionExpiration());
	}

	protected function hashSessionId(string $sessionId): string {
		// As of writing same implementation as in private's PublicKeyProvider`s
		// hashToken() method. It is not public API and a private detail, so we
		// cannot assume it always stays the same. Hence, even though it is
		// at this moment identical to oc_authtoken.token (also not exposed),
		// it is not something we can take for granted and therefore store the
		// token ID as well.
		$secret = $this->config->getSystemValueString('secret');
		return hash('sha512', $sessionId . $secret);
	}

	public function storeSessionDataInDatabase(): void {
		$sessionDataModel = SessionDataModel::fromSession($this->session);

		$sessionData = new SessionData();
		/** @psalm-suppress InvalidArgument setId requires a string not an int */
		$sessionData->setId($this->hashSessionId($this->session->getId()));
		$sessionData->setTokenId($this->tokenProvider->getToken($this->session->getId())->getId());
		$sessionData->setData($sessionDataModel);

		$this->sessionDataMapper->insert($sessionData);
	}

	protected function storeSessionDataInSession(SessionData $sessionData): void {
		$model = $sessionData->getData();
		foreach ($model->jsonSerialize() as $sessionKey => $value) {
			$this->session->set($sessionKey, $value);
		}
	}
}
