<?php

namespace W4Services\W4Payrexx\Models;

class Gateway
{

    /**
     * @var integer
     */
    private $amount;

    /**
     * optional
     * @var float|null
     */
    private $vatRate;

    /**
     * @var string
     */
    private $currency;

    /**
     * optional
     * @var string
     */
    private $referenceId;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var string
     */
    private $successRedirectUrl;

    /**
     * @var string
     */
    private $failedRedirectUrl;

    /**
     * @var string
     */
    private $cancelRedirectUrl;

    /**
     * @return int
     */
    public function getAmount(): int
    {
        return $this->amount;
    }

    /**
     * @param int $amount
     */
    public function setAmount(int $amount): void
    {
        $this->amount = $amount;
    }

    /**
     * @return float|null
     */
    public function getVatRate(): ?float
    {
        return $this->vatRate;
    }

    /**
     * @param float|null $vatRate
     */
    public function setVatRate(?float $vatRate): void
    {
        $this->vatRate = $vatRate;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
    }

    /**
     * @param string $currency
     */
    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    /**
     * @return string
     */
    public function getReferenceId(): string
    {
        return $this->referenceId;
    }

    /**
     * @param string $referenceId
     */
    public function setReferenceId(string $referenceId): void
    {
        $this->referenceId = $referenceId;
    }

    /**
     * @return array
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return string
     */
    public function getSuccessRedirectUrl(): string
    {
        return $this->successRedirectUrl;
    }

    /**
     * @param string $successRedirectUrl
     */
    public function setSuccessRedirectUrl(string $successRedirectUrl): void
    {
        $this->successRedirectUrl = $successRedirectUrl;
    }

    /**
     * @return string
     */
    public function getFailedRedirectUrl(): string
    {
        return $this->failedRedirectUrl;
    }

    /**
     * @param string $failedRedirectUrl
     */
    public function setFailedRedirectUrl(string $failedRedirectUrl): void
    {
        $this->failedRedirectUrl = $failedRedirectUrl;
    }

    /**
     * @return string
     */
    public function getCancelRedirectUrl(): string
    {
        return $this->cancelRedirectUrl;
    }

    /**
     * @param string $cancelRedirectUrl
     */
    public function setCancelRedirectUrl(string $cancelRedirectUrl): void
    {
        $this->cancelRedirectUrl = $cancelRedirectUrl;
    }

    /**
     * Add a new field of the payment page
     *
     * @access  public
     * @param   string  $type           Type of field
     *                                  Available types: title, forename, surname, company, street,
     *                                  postcode, place, country, phone, email, date_of_birth,
     *                                  custom_field_1, custom_field_2, custom_field_3, custom_field_4, custom_field_5
     * @param   string  $value          Value of field
     *                                  For field of type "title" use value "mister" or "miss"
     *                                  For field of type "country" pass the 2 letter ISO code
     * @param   array   $name           Name of the field (only available for fields of type "custom_field_1-5"
     */
    public function addField($type, $value, $name = array())
    {
        $this->fields[$type] = [
            'value' => $value,
            'name' => $name,
        ];
    }

    public function toArray()
    {
        return get_object_vars($this);
    }
}
