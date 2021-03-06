<?php declare(strict_types=1);

namespace Shopware\Core\Content\Test\GoogleShopping\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Test\GoogleShopping\GoogleShoppingIntegration;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\AdminFunctionalTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\HttpFoundation\Response;
use function Flag\skipTestNext6050;

class GoogleShoppingAccountControllerTest extends TestCase
{
    use AdminFunctionalTestBehaviour;
    use GoogleShoppingIntegration;

    /**
     * @var Context
     */
    private $context;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    private $client;

    protected function setUp(): void
    {
        skipTestNext6050($this);
        $this->context = Context::createDefaultContext();
        $this->getMockGoogleClient();
        $this->client = $this->getBrowser();
    }

    public function testAccountConnectFails(): void
    {
        $salesChannelId = $this->createSalesChannelGoogleShopping();

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $salesChannelId . '/google-shopping/account/connect'
        );

        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertStringContainsString('CONTENT__GOOGLE_SHOPPING_INVALID_AUTHORIZATION_CODE', $response->getContent());

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $salesChannelId . '/google-shopping/account/connect',
            ['code' => 'GOOGLE.INVALID.CODE']
        );

        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertStringContainsString('CONTENT__GOOGLE_SHOPPING_INVALID_AUTHORIZATION_CODE', $response->getContent());

        $googleAccounts = $this->createGoogleShoppingAccount(Uuid::randomHex(), $salesChannelId);

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $googleAccounts['googleAccount']['salesChannelId'] . '/google-shopping/account/connect',
            ['code' => 'VALID.AUTHORIZATION.CODE']
        );

        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        static::assertStringContainsString('CONTENT__GOOGLE_SHOPPING_ALREADY_CONNECTED_ACCOUNT', $response->getContent());
    }

    public function testAccountConnectSuccess(): void
    {
        $salesChannelId = $this->createSalesChannelGoogleShopping();

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $salesChannelId . '/google-shopping/account/connect',
            ['code' => 'VALID.AUTHORIZATION.CODE']
        );

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testAccountDisconnectFails(): void
    {
        $salesChannelId = $this->createSalesChannelGoogleShopping();

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $salesChannelId . '/google-shopping/account/disconnect'
        );

        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        static::assertStringContainsString('CONTENT__GOOGLE_SHOPPING_CONNECTED_ACCOUNT_NOT_FOUND', $response->getContent());
    }

    public function testAccountDisconnectSuccess(): void
    {
        $googleAccount = $this->createGoogleShoppingAccount(Uuid::randomHex());

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $googleAccount['googleAccount']['salesChannelId'] . '/google-shopping/account/disconnect'
        );

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }

    public function testGetAccountUserProfileSuccess(): void
    {
        $googleAccount = $this->createGoogleShoppingAccount(Uuid::randomHex());

        $this->client->request(
            'GET',
            '/api/v1/_action/sales-channel/' . $googleAccount['googleAccount']['salesChannelId'] . '/google-shopping/account/profile'
        );

        $response = $this->client->getResponse();

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());

        $content = json_decode($response->getContent(), true);

        static::assertEquals([
            'verified_email' => true,
            'given_name' => 'John',
            'family_name' => 'Joe',
            'email' => 'john.doe@example.com',
            'id' => '1234567890',
            'locale' => 'en',
            'name' => 'John Doe',
            'picture' => 'https://lh3.googleusercontent.com/a-/AOh14Ghvc3v9xTUIDTCW67gcdolbfBlHMoHYSFLc6hglZA', ], $content['data']);
    }

    public function testAccountAgreeTermOfServiceWithoutAcceptanceFailure(): void
    {
        $googleAccount = $this->createGoogleShoppingAccount(Uuid::randomHex());

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $googleAccount['googleAccount']['salesChannelId'] . '/google-shopping/account/accept-term-of-service'
        );

        $response = $this->client->getResponse()->getContent();
        $response = json_decode($response, true);

        static::assertArrayHasKey('errors', $response);
        static::assertCount(1, $response['errors']);
        static::assertSame('/acceptance', $response['errors'][0]['source']['pointer']);
    }

    public function testAccountAgreeTermOfServiceWithInvalidAcceptanceFailure(): void
    {
        $googleAccount = $this->createGoogleShoppingAccount(Uuid::randomHex());

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $googleAccount['googleAccount']['salesChannelId'] . '/google-shopping/account/accept-term-of-service',
            ['acceptance' => 'not_a_boolean_value']
        );

        $response = $this->client->getResponse()->getContent();
        $response = json_decode($response, true);

        static::assertArrayHasKey('errors', $response);
        static::assertCount(1, $response['errors']);
        static::assertSame('/acceptance', $response['errors'][0]['source']['pointer']);
    }

    public function testAccountAgreeTermOfServiceSuccess(): void
    {
        $googleShoppingAccountRepository = $this->getContainer()->get('google_shopping_account.repository');
        $context = Context::createDefaultContext();
        $googleAccount = $this->createGoogleShoppingAccount(Uuid::randomHex());

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $googleAccount['googleAccount']['salesChannelId'] . '/google-shopping/account/accept-term-of-service',
            ['acceptance' => true]
        );

        static::assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $response = $this->client->getResponse()->getContent();
        $response = json_decode($response, true);
        static::assertArrayHasKey('data', $response);
        static::assertEquals($googleAccount['id'], $response['data']);

        $account = $googleShoppingAccountRepository->search(new Criteria([$googleAccount['id']]), $context)->first();
        static::assertNotNull($account->getTosAcceptedAt());

        $this->client->request(
            'POST',
            '/api/v1/_action/sales-channel/' . $googleAccount['googleAccount']['salesChannelId'] . '/google-shopping/account/accept-term-of-service',
            ['acceptance' => false]
        );

        $account = $googleShoppingAccountRepository->search(new Criteria([$googleAccount['id']]), $context)->first();
        static::assertNull($account->getTosAcceptedAt());
    }
}
