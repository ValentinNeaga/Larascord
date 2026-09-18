<?php

namespace Jakyeru\Larascord\Types;

class User
{
    /**
     * The user's id.
     */
    public string $id;

    /**
     * The user's unique username.
     */
    public string $username;

    /**
     * The user's global display name.
     */
    public ?string $global_name;

    /**
     * The user's display name, falling back to the username.
     */
    public string $display_name;

    /**
     * The user's discriminator.
     */
    public ?string $discriminator;

    /**
     * The user's avatar hash.
     */
    public ?string $avatar;

    /**
     * The user's email.
     */
    public ?string $email;

    /**
     * The user's email verification status.
     */
    public ?bool $verified;

    /**
     * The user's banner hash.
     */
    public ?string $banner;

    /**
     * The user's banner color.
     */
    public ?string $banner_color;

    /**
     * The user's accent color.
     */
    public ?string $accent_color;

    /**
     * The user's locale.
     */
    public ?string $locale;

    /**
     * The user's multifactor authentication status.
     */
    public ?bool $mfa_enabled;

    /**
     * The user's premium type.
     */
    public ?int $premium_type;

    /**
     * The user's public flags.
     */
    public ?int $public_flags;

    /**
     * The user's access token.
     */
    public ?AccessToken $access_token;

    /**
     * User constructor.
     */
    public function __construct(object $data)
    {
        $this->id = $data->id;
        $this->username = $data->username;
        $this->global_name = $data->global_name ?? NULL;
        $this->display_name = $this->global_name ?: $this->username;
        $this->discriminator = $data->discriminator ?? NULL;
        $this->avatar = $data->avatar ?? NULL;
        $this->email = $data->email ?? NULL;
        $this->verified = $data->verified ?? NULL;
        $this->banner = $data->banner ?? NULL;
        $this->banner_color = $data->banner_color ?? NULL;
        $this->accent_color = $data->accent_color ?? NULL;
        $this->locale = $data->locale ?? NULL;
        $this->mfa_enabled = $data->mfa_enabled ?? NULL;
        $this->premium_type = $data->premium_type ?? NULL;
        $this->public_flags = $data->public_flags ?? NULL;
        $this->access_token = NULL;

        return $this;
    }

    /**
     * Get the user's id.
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the user's username.
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Get the user's global display name.
     */
    public function getGlobalName(): ?string
    {
        return $this->global_name;
    }

    /**
     * Get the user's display name.
     */
    public function getDisplayName(): string
    {
        return $this->display_name;
    }

    /**
     * Get the user's discriminator.
     */
    public function getDiscriminator(): ?string
    {
        return $this->discriminator;
    }

    /**
     * Get the user's tag.
     */
    public function getTag(): string
    {
        if ($this->hasMigratedToUsernames()) {
            return $this->display_name;
        }

        return $this->username . '#' . $this->discriminator;
    }

    /**
     * Get the user's avatar hash.
     */
    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    /**
     * Get the user's email.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Get the user's email verification status.
     */
    public function getVerified(): ?bool
    {
        return $this->verified;
    }

    /**
     * Get the user's banner hash.
     */
    public function getBanner(): ?string
    {
        return $this->banner;
    }

    /**
     * Get the user's banner color.
     */
    public function getBannerColor(): ?string
    {
        return $this->banner_color;
    }

    /**
     * Get the user's accent color.
     */
    public function getAccentColor(): ?string
    {
        return $this->accent_color;
    }

    /**
     * Get the user's locale.
     */
    public function getLocale(): ?string
    {
        return $this->locale;
    }

    /**
     * Get the user's multifactor authentication status.
     */
    public function getMfaEnabled(): ?bool
    {
        return $this->mfa_enabled;
    }

    /**
     * Get the user's premium type.
     */
    public function getPremiumType(): ?int
    {
        return $this->premium_type;
    }

    /**
     * Get the user's public flags.
     */
    public function getPublicFlags(): ?int
    {
        return $this->public_flags;
    }

    /**
     * Get the user's access token.
     */
    public function getAccessToken(): ?AccessToken
    {
        return $this->access_token;
    }

    /**
     * Set the user's access token.
     */
    public function setAccessToken(AccessToken $accessToken): self
    {
        $this->access_token = $accessToken;

        return $this;
    }

    /**
     * Whether the user has migrated to the new username system.
     */
    public function hasMigratedToUsernames(): bool
    {
        return $this->discriminator == '0' && $this->global_name;
    }

    /**
     * Convert the user to an array.
     */
    public function toArray(): array
    {
        return [
            'discord_id' => $this->id,
            'username' => $this->username,
            'global_name' => $this->global_name,
            'discriminator' => $this->discriminator,
            'avatar' => $this->avatar,
            'email' => $this->email,
            'verified' => $this->verified,
            'banner' => $this->banner,
            'banner_color' => $this->banner_color,
            'accent_color' => $this->accent_color,
            'locale' => $this->locale,
            'mfa_enabled' => $this->mfa_enabled,
            'premium_type' => $this->premium_type,
            'public_flags' => $this->public_flags,
        ];
    }
}