<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.OperationType)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 */
class OperationType extends \ProtocolBuffers\Enum
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.OperationType)
  
  const PUT = 1;
  const DELETE = 2;
  
  // @@protoc_insertion_point(const_scope:.ots.protocolbuffer.OperationType)
  
  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.OperationType)
  
  /**
   * @return \ProtocolBuffers\EnumDescriptor
   */
  public static function getEnumDescriptor()
  {
    static $descriptor;
    if (!$descriptor) {
      $builder = new \ProtocolBuffers\EnumDescriptorBuilder();
      $builder->addValue(new \ProtocolBuffers\EnumValueDescriptor(array(
        "value" => self::PUT,
        "name"  => 'PUT',
      )));
      $builder->addValue(new \ProtocolBuffers\EnumValueDescriptor(array(
        "value" => self::DELETE,
        "name"  => 'DELETE',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.OperationType)
      $descriptor = $builder->build();
    }
    return $descriptor;
  }
}
