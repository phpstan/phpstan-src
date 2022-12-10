<?php

declare(strict_types=1);

namespace Bug8146a;

class HelloWorld
{
	private SessionInterface $session;
	private DataObject       $object;

	public function __construct(SessionInterface $session, DataObject $object)
	{
		$this->session = $session;
		$this->object  = $object;
	}

	public function sayHello(): void
	{
		$changeLog = [];

		$firstname = $this->session->get('firstname');
		if ($firstname !== $this->object->getFirstname()) {
			$changelog['firstname_old'] = $this->object->getFirstname();
			$changelog['firstname_new'] = $firstname;
		}

		$lastname = $this->session->get('lastname');
		if ($lastname !== $this->object->getLastname()) {
			$changelog['lastname_old'] = $this->object->getLastname();
			$changelog['lastname_new'] = $lastname;
		}

		$street = $this->session->get('street');
		if ($street !== $this->object->getStreet()) {
			$changelog['street_old'] = $this->object->getStreet();
			$changelog['street_new'] = $street;
		}

		$zip = $this->session->get('zip');
		if ($zip !== $this->object->getZip()) {
			$changelog['zip_old'] = $this->object->getZip();
			$changelog['zip_new'] = $zip;
		}

		$city = $this->session->get('city');
		if ($city !== $this->object->getCity()) {
			$changelog['city_old'] = $this->object->getCity();
			$changelog['city_new'] = $city;
		}

		$phonenumber = $this->session->get('phonenumber');
		if ($phonenumber !== $this->object->getPhonenumber()) {
			$changelog['phonenumber_old'] = $this->object->getPhonenumber();
			$changelog['phonenumber_new'] = $phonenumber;
		}

		$email = $this->session->get('email');
		if ($email !== $this->object->getEmail()) {
			$changelog['email_old'] = $this->object->getEmail();
			$changelog['email_new'] = $email;
		}

		$deliveryFirstname = $this->session->get('deliveryFirstname');
		if ($deliveryFirstname !== $this->object->getDeliveryFirstname()) {
			$changelog['deliveryFirstname_old'] = $this->object->getDeliveryFirstname();
			$changelog['deliveryFirstname_new'] = $deliveryFirstname;
		}

		$deliveryLastname = $this->session->get('deliveryLastname');
		if ($deliveryLastname !== $this->object->getDeliveryLastname()) {
			$changelog['deliveryLastname_old'] = $this->object->getDeliveryLastname();
			$changelog['deliveryLastname_new'] = $deliveryLastname;
		}
		$deliveryStreet = $this->session->get('deliveryStreet');
		if ($deliveryStreet !== $this->object->getDeliveryStreet()) {
			$changelog['deliveryStreet_old'] = $this->object->getDeliveryStreet();
			$changelog['deliveryStreet_new'] = $deliveryStreet;
		}
		$deliveryZip = $this->session->get('deliveryZip');
		if ($deliveryZip !== $this->object->getDeliveryZip()) {
			$changelog['deliveryZip_old'] = $this->object->getDeliveryZip();
			$changelog['deliveryZip_new'] = $deliveryZip;
		}
		$deliveryCity = $this->session->get('deliveryCity');
		if ($deliveryCity !== $this->object->getDeliveryCity()) {
			$changelog['deliveryCity_old'] = $this->object->getDeliveryCity();
			$changelog['deliveryCity_new'] = $deliveryCity;
		}

	}
}

interface SessionInterface
{
	/**
	 * @return mixed
	 */
	public function get(string $key);
}

interface DataObject
{
	/**
	 * @return string|null
	 */
	public function getFirstname();
	/**
	 * @return string|null
	 */
	public function getLastname();
	/**
	 * @return string|null
	 */
	public function getStreet();
	/**
	 * @return string|null
	 */
	public function getZip();
	/**
	 * @return string|null
	 */
	public function getCity();
	/**
	 * @return string|null
	 */
	public function getPhonenumber();
	/**
	 * @return string|null
	 */
	public function getEmail();
	/**
	 * @return string|null
	 */
	public function getDeliveryFirstname();
	/**
	 * @return string|null
	 */
	public function getDeliveryLastname();
	/**
	 * @return string|null
	 */
	public function getDeliveryStreet();
	/**
	 * @return string|null
	 */
	public function getDeliveryZip();
	/**
	 * @return string|null
	 */
	public function getDeliveryCity();
}
